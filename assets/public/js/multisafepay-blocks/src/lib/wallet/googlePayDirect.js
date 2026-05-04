import { createElement, createPortal, useEffect, useRef, useState } from '@wordpress/element';

import { getCustomerBrowserInfo } from '../browserInfo';
import { multisafepayBlocksSummarizeError, multisafepayBlocksTruncateString } from '../errors';
import { multisafepayBlocksRegisterOnPaymentSubmit } from '../paymentEvents';
import {
    getBlocksCheckoutActionsContainer,
    getPreferredPlaceOrderButtonHeightPx,
    setBlocksActivePaymentMethodId,
    setBlocksPaymentMethodData,
    setBlocksPlaceOrderHiddenByWallet,
    triggerBlocksPlaceOrderClick,
} from '../dom/checkout';
import {
    cleanupMultisafepayWalletActionsHostIfEmptyForOwner,
    ensureMultisafepayWalletActionsHostSlot,
    hasMultisafepayWalletActionsHostAnyContent,
    syncMultisafepayWalletActionsHostToPlaceOrder,
} from './actionsHost';
import { multisafepayBlocksRetryUntil } from '../dom/retry';
import {
    getPreferredWalletButtonBorderRadiusPx,
    getPreferredWalletButtonHeightPx,
    getPreferredWalletButtonMaxWidthPx,
    isApplePayCompatibleForGooglePayStylingFallback,
    multisafepayBlocksDebugGooglePayButtonComputed,
    multisafepayBlocksEnsureGooglePayCssOverrides,
    multisafepayBlocksNormalizeGooglePayButtonStyles,
} from './googlePayStyles';

/**
 * Google Pay "direct" wallet button content for Checkout Blocks.
 *
 * Renders a Google Pay button in the checkout actions area and, when clicked,
 * performs tokenization, writes payment meta into the Blocks payment store and
 * triggers checkout submission.
 *
 * @param {Object} props
 * @param {Object} props.gateway Gateway data localized from PHP.
 * @param {Object} [props.eventRegistration] Blocks runtime events.
 * @returns {Object|null} Portal element or null.
 */
export const GooglePayDirectContent       = ( props ) => {
    const { eventRegistration, gateway }  = props;
    const containerRef                    = useRef( null );
    const clientRef                       = useRef( null );
    const tokenRef                        = useRef( '' );
    const browserRef                      = useRef( '' );
    const [ error, setError ]             = useState( '' );
    const [ actionsHost, setActionsHost ] = useState( null );
    const walletConfig                    = gateway && gateway.wallet_config ? gateway.wallet_config : null;

    const debugEnabled = ! ! ( walletConfig && walletConfig.debugMode );
    const logDebug     = ( ...args ) => {
        if ( ! debugEnabled ) {
            return;
        }
        try {
            // eslint-disable-next-line no-console
            console.log( ...args );
        } catch ( e ) {
            // no-op
        }
    };

    const debugRef = useRef(
        {
            initLogged: false,
            readyLogged: false,
            buttonRenderedLogged: false,
            buttonComputedLogged: false,
        }
    );

    /**
     * Fetches the latest checkout total from the server.
     *
     * Reuses the existing wallet total endpoint used by the classic checkout
     * and normalizes the value to major currency units.
     *
     * @returns {Promise<number|null>} Total in major units, or null when unavailable.
     */
    const getUpdatedTotalPrice = async() => {
        const ajaxUrl          = gateway && gateway.ajax_url ? String( gateway.ajax_url ) : '';
        const nonce            = gateway && gateway.nonce ? String( gateway.nonce ) : '';

        if ( ! ajaxUrl || ! nonce ) {
            return null;
        }

        try {
            const data = new URLSearchParams();
            data.append( 'action', 'get_updated_total_price' );
            data.append( 'nonce', nonce );

            const response = await fetch(
                ajaxUrl,
                {
                    method: 'POST',
                    body: data,
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    credentials: 'same-origin',
                }
            );

            const json       = await response.json();
            const totalMinor = json && typeof json.totalPrice !== 'undefined' ? Number( json.totalPrice ) : NaN;

            if ( Number.isNaN( totalMinor ) || totalMinor < 0 ) {
                return null;
            }

            return Math.round( totalMinor ) / 100;
        } catch ( err ) {
            logDebug( '[GooglePay] refresh totalPrice failed', multisafepayBlocksSummarizeError( err ) );
            return null;
        }
    };

    /**
     * Builds the request payload for `PaymentsClient.loadPaymentData`.
     *
     * Kept as a function to avoid recreating the object in multiple places and to
     * make the click handler easier to scan.
     *
     * @returns {Object}
     */
    const buildPaymentDataRequest       = ( totalPriceOverride = null ) => {
        const baseRequest               = { apiVersion: 2, apiVersionMinor: 0 };
        const tokenizationSpecification = {
            type: 'PAYMENT_GATEWAY',
            parameters: {
                gateway: 'multisafepay',
                gatewayMerchantId: String( walletConfig.gatewayMerchantId ),
            },
        };
        const allowedCardNetworks       = [ 'MASTERCARD', 'VISA' ];
        const allowedCardAuthMethods    = [ 'CRYPTOGRAM_3DS', 'PAN_ONLY' ];
        const baseCardPaymentMethod     = {
            type: 'CARD',
            parameters: {
                allowedAuthMethods: allowedCardAuthMethods,
                allowedCardNetworks: allowedCardNetworks,
            },
        };
        const cardPaymentMethod         = Object.assign( { tokenizationSpecification }, baseCardPaymentMethod );
        const normalizedTotalPrice      = ( typeof totalPriceOverride === 'number' && ! Number.isNaN( totalPriceOverride ) )
            ? totalPriceOverride
            : Number( walletConfig.totalPrice || 0 );

        const paymentDataRequest                 = Object.assign( {}, baseRequest );
        paymentDataRequest.allowedPaymentMethods = [ cardPaymentMethod ];
        paymentDataRequest.transactionInfo       = {
            totalPriceStatus: 'FINAL',
            totalPrice: normalizedTotalPrice.toFixed( 2 ),
            currencyCode: String( walletConfig.currencyCode || '' ),
            countryCode: String( walletConfig.countryCode || '' ),
        };
        paymentDataRequest.merchantInfo          = {
            merchantName: String( walletConfig.merchantName || '' ),
            merchantId: String( walletConfig.merchantId || '' ),
        };

        return paymentDataRequest;
    };

    const onClick = async() => {
        setError( '' );

        const client = clientRef.current;
        if ( ! client || ! client.loadPaymentData ) {
            setError( 'Google Pay is not available right now.' );
            logDebug( '[GooglePay] click: client not ready' );
            return;
        }

        try {
            logDebug( '[GooglePay] click: loadPaymentData() start' );
            const refreshedTotalPrice = await getUpdatedTotalPrice();
            if ( typeof refreshedTotalPrice === 'number' && ! Number.isNaN( refreshedTotalPrice ) ) {
                logDebug(
                    '[GooglePay] click: refreshed totalPrice',
                    {
                        totalPrice: multisafepayBlocksTruncateString( String( refreshedTotalPrice ), 20 ),
                    }
                );
            }

            const request     = buildPaymentDataRequest( refreshedTotalPrice );
            const paymentData = await client.loadPaymentData( request );

            const token = paymentData &&
                paymentData.paymentMethodData &&
                paymentData.paymentMethodData.tokenizationData &&
                paymentData.paymentMethodData.tokenizationData.token
                ? paymentData.paymentMethodData.tokenizationData.token
                : '';

            if ( ! token || typeof token !== 'string' ) {
                setError( 'Google Pay returned an invalid token.' );
                logDebug( '[GooglePay] click: invalid token', { hasToken: ! ! token, tokenType: typeof token } );
                return;
            }

            tokenRef.current   = token;
            browserRef.current = getCustomerBrowserInfo();

            // Debug: do not log token contents, only small summaries.
            logDebug(
                '[GooglePay] click: token captured',
                {
                    tokenLength: token.length,
                    browserJsonLength: typeof browserRef.current === 'string' ? browserRef.current.length : 0,
                }
            );

            // Trigger checkout submission; the payment submit callback
            // will inject paymentMethodData into the Store API request.
            const methodId = gateway && gateway.id ? gateway.id : '';
            setBlocksActivePaymentMethodId( methodId );
            if ( methodId ) {
                const paymentMethodData                          = {};
                paymentMethodData[ methodId + '_payment_token' ] = token;
                paymentMethodData[ methodId + '_browser' ]       = browserRef.current;
                setBlocksPaymentMethodData( paymentMethodData );
            }
            const clicked = triggerBlocksPlaceOrderClick();
            if ( ! clicked ) {
                setError( 'Could not submit checkout automatically. Please click Place order.' );
                logDebug( '[GooglePay] click: could not auto-submit (place order not found)' );
            } else {
                logDebug( '[GooglePay] click: triggered place order' );
            }
        } catch ( e ) {
            logDebug( 'Google Pay Direct error:', e );
            logDebug( '[GooglePay] click: loadPaymentData failed', multisafepayBlocksSummarizeError( e ) );
            setError( 'Google Pay was cancelled or failed.' );
        }
    };

    useEffect(
        () => {
            if ( ! walletConfig || ! walletConfig.gatewayMerchantId ) {
                return;
            }
            if ( debugEnabled && ! debugRef.current.initLogged ) {
                debugRef.current.initLogged = true;
                logDebug(
                    '[GooglePay] init',
                    {
                        env: walletConfig.environment === 'LIVE' ? 'LIVE' : 'TEST',
                        country: String( walletConfig.countryCode || '' ),
                        currency: String( walletConfig.currencyCode || '' ),
                        totalPrice: multisafepayBlocksTruncateString( String( walletConfig.totalPrice || '' ), 20 ),
                        merchantIdPresent: ! ! walletConfig.merchantId,
                        gatewayMerchantIdPresent: ! ! walletConfig.gatewayMerchantId,
                    }
                );
            }
            if ( ! window.google || ! window.google.payments || ! window.google.payments.api ) {
                setError( 'Google Pay script not loaded.' );
                logDebug( '[GooglePay] script not loaded (window.google.payments.api missing)' );
                return;
            }
            const env         = walletConfig.environment === 'LIVE' ? 'PRODUCTION' : 'TEST';
            const client      = new window.google.payments.api.PaymentsClient( { environment: env } );
            clientRef.current = client;
            // Attempt a readiness check; if it fails, do not render the button.
            const baseRequest                         = { apiVersion: 2, apiVersionMinor: 0 };
            const allowedCardNetworks                 = [ 'MASTERCARD', 'VISA' ];
            const allowedCardAuthMethods              = [ 'CRYPTOGRAM_3DS', 'PAN_ONLY' ];
            const isReadyToPayRequest                 = Object.assign( {}, baseRequest );
            isReadyToPayRequest.allowedPaymentMethods = [
                {
                    type: 'CARD',
                    parameters: {
                        allowedAuthMethods: allowedCardAuthMethods,
                        allowedCardNetworks: allowedCardNetworks,
                    },
            },
            ];
            let cancelled                             = false;
            client.isReadyToPay( isReadyToPayRequest )
                .then(
                    ( response ) => {
                        if ( cancelled ) {
                            return;
                        }
                        if ( debugEnabled && ! debugRef.current.readyLogged ) {
                            debugRef.current.readyLogged = true;
                            logDebug( '[GooglePay] isReadyToPay response', { result: ! ! ( response && response.result ) } );
                        }
                        if ( ! response || ! response.result ) {
                            // Not available for this browser/customer.
                            return;
                        }
                        if ( ! containerRef.current ) {
                            return;
                        }
                        try {
                            containerRef.current.innerHTML = '';
                            const applePayCompatible       = isApplePayCompatibleForGooglePayStylingFallback();

                            // If Apple Pay is available on this device (legacy criterion), avoid custom
                            // sizing/styling — let Google render the button as-is.
                            // This can reduce WebKit logo scaling/layout quirks on Apple browsers.
                            if ( applePayCompatible ) {
                                multisafepayBlocksEnsureGooglePayCssOverrides();

                                try {
                                    containerRef.current.removeAttribute( 'style' );
                                } catch ( e ) {
                                    // no-op
                                }

                                // Apply the same sizing constraints as the standard Blocks render.
                                const inActions      = ! ! actionsHost;
                                const heightPx       = inActions ? getPreferredPlaceOrderButtonHeightPx() : getPreferredWalletButtonHeightPx();
                                const maxWidthPx     = inActions ? 0 : getPreferredWalletButtonMaxWidthPx();
                                const borderRadiusPx = getPreferredWalletButtonBorderRadiusPx();

                                // Use the same payment methods as loadPaymentData().
                                // This also lets us pass richer button customization options.
                                let allowedPaymentMethodsForButton = [];
                                try {
                                    const paymentDataRequest = buildPaymentDataRequest();
                                    if ( paymentDataRequest && Array.isArray( paymentDataRequest.allowedPaymentMethods ) ) {
                                        allowedPaymentMethodsForButton = paymentDataRequest.allowedPaymentMethods;
                                    }
                                } catch ( e ) {
                                    // no-op
                                }

                                const buttonLocale = String( ( window.navigator && window.navigator.language ) ? window.navigator.language : 'en' );

                                try {
                                    containerRef.current.style.setProperty( 'height', heightPx + 'px', 'important' );
                                    containerRef.current.style.setProperty( 'min-height', heightPx + 'px', 'important' );
                                    containerRef.current.style.setProperty( 'max-height', heightPx + 'px', 'important' );

                                    containerRef.current.style.setProperty( 'width', '100%', 'important' );
                                    if ( maxWidthPx > 0 ) {
                                        containerRef.current.style.setProperty( 'max-width', maxWidthPx + 'px', 'important' );
                                        containerRef.current.style.setProperty( 'margin-left', 'auto', 'important' );
                                        containerRef.current.style.setProperty( 'margin-right', 'auto', 'important' );
                                    } else {
                                        containerRef.current.style.setProperty( 'max-width', '100%', 'important' );
                                        containerRef.current.style.setProperty( 'margin-left', '0', 'important' );
                                        containerRef.current.style.setProperty( 'margin-right', '0', 'important' );
                                    }

                                    if ( borderRadiusPx > 0 ) {
                                        containerRef.current.style.setProperty( 'border-radius', borderRadiusPx + 'px', 'important' );
                                        containerRef.current.style.setProperty( 'overflow', 'hidden', 'important' );
                                    }

                                    containerRef.current.style.setProperty( 'display', 'block', 'important' );
                                } catch ( e ) {
                                    // no-op
                                }

                                // On Apple/WebKit browsers the default button can look odd (e.g., "Buy with GPay").
                                // Force "plain" for a cleaner look.
                                const button = client.createButton(
                                    {
                                        buttonColor: 'default',
                                        buttonType: 'plain',
                                        buttonSizeMode: 'fill',
                                        buttonRadius: borderRadiusPx,
                                        buttonBorderType: 'default_border',
                                        buttonLocale: buttonLocale,
                                        allowedPaymentMethods: allowedPaymentMethodsForButton,
                                        onClick: onClick,
                                    }
                                );

                                multisafepayBlocksNormalizeGooglePayButtonStyles( containerRef.current, borderRadiusPx );
                                multisafepayBlocksNormalizeGooglePayButtonStyles( button, borderRadiusPx );

                                try {
                                    if ( button && button.style ) {
                                        button.style.display = 'block';
                                        button.style.height  = '100%';
                                        button.style.width   = '100%';
                                        if ( borderRadiusPx > 0 && typeof button.style.setProperty === 'function' ) {
                                            button.style.setProperty( 'border-radius', borderRadiusPx + 'px', 'important' );
                                            button.style.setProperty( 'overflow', 'hidden', 'important' );
                                        }
                                    }
                                } catch ( e ) {
                                    // no-op
                                }

                                containerRef.current.appendChild( button );
                                if ( debugEnabled && ! debugRef.current.buttonRenderedLogged ) {
                                    debugRef.current.buttonRenderedLogged = true;
                                    logDebug( '[GooglePay] button rendered', { applePayCompatible: true, mode: 'google-default' } );
                                }
                                if ( debugEnabled && ! debugRef.current.buttonComputedLogged ) {
                                    debugRef.current.buttonComputedLogged = true;
                                    multisafepayBlocksDebugGooglePayButtonComputed( logDebug, containerRef.current );
                                }

                                setBlocksPlaceOrderHiddenByWallet( ! ! actionsHost );

                                if ( actionsHost ) {
                                    syncMultisafepayWalletActionsHostToPlaceOrder( actionsHost, getBlocksCheckoutActionsContainer() );
                                }
                                return;
                            }

                            // Apply sizing constraints to better match WC Blocks UI.
                            // Make height theme-configurable by matching the Place order button height.
                            // This prevents the Google Pay button from stretching too tall when using
                            // buttonSizeMode: 'fill'.
                            const inActions      = ! ! actionsHost;
                            const heightPx       = inActions ? getPreferredPlaceOrderButtonHeightPx() : getPreferredWalletButtonHeightPx();
                            const maxWidthPx     = inActions ? 0 : getPreferredWalletButtonMaxWidthPx();
                            const borderRadiusPx = getPreferredWalletButtonBorderRadiusPx();
                            containerRef.current.style.setProperty( 'height', heightPx + 'px', 'important' );
                            containerRef.current.style.setProperty( 'min-height', heightPx + 'px', 'important' );
                            containerRef.current.style.setProperty( 'max-height', heightPx + 'px', 'important' );

                            // Avoid 100% width on large screens: keep it centered with a max-width.
                            containerRef.current.style.setProperty( 'width', '100%', 'important' );
                            if ( maxWidthPx > 0 ) {
                                containerRef.current.style.setProperty( 'max-width', maxWidthPx + 'px', 'important' );
                                containerRef.current.style.setProperty( 'margin-left', 'auto', 'important' );
                                containerRef.current.style.setProperty( 'margin-right', 'auto', 'important' );
                            } else {
                                containerRef.current.style.setProperty( 'max-width', '100%', 'important' );
                                containerRef.current.style.setProperty( 'margin-left', '0', 'important' );
                                containerRef.current.style.setProperty( 'margin-right', '0', 'important' );
                            }

                            if ( borderRadiusPx > 0 ) {
                                containerRef.current.style.setProperty( 'border-radius', borderRadiusPx + 'px', 'important' );
                                containerRef.current.style.setProperty( 'overflow', 'hidden', 'important' );
                            }

                            containerRef.current.style.setProperty( 'display', 'block', 'important' );

                            const buttonSizeMode = 'fill';
                            const button         = client.createButton(
                                {
                                    buttonType: 'plain',
                                    buttonColor: 'black',
                                    buttonSizeMode: buttonSizeMode,
                                    onClick: onClick,
                                }
                            );

                            multisafepayBlocksEnsureGooglePayCssOverrides();
                            multisafepayBlocksNormalizeGooglePayButtonStyles( containerRef.current, borderRadiusPx );
                            multisafepayBlocksNormalizeGooglePayButtonStyles( button, borderRadiusPx );

                            try {
                                if ( button && button.style ) {
                                    button.style.display = 'block';
                                    button.style.height  = '100%';
                                    button.style.width   = '100%';
                                    if ( borderRadiusPx > 0 && typeof button.style.setProperty === 'function' ) {
                                        button.style.setProperty( 'border-radius', borderRadiusPx + 'px', 'important' );
                                        button.style.setProperty( 'overflow', 'hidden', 'important' );
                                    }
                                }
                            } catch ( e ) {
                                // no-op
                            }

                            containerRef.current.appendChild( button );

                            if ( debugEnabled && ! debugRef.current.buttonRenderedLogged ) {
                                debugRef.current.buttonRenderedLogged = true;
                                logDebug(
                                    '[GooglePay] button rendered',
                                    {
                                        heightPx: heightPx,
                                        maxWidthPx: maxWidthPx,
                                        borderRadiusPx: borderRadiusPx,
                                        buttonSizeMode: buttonSizeMode,
                                        applePayCompatible: false,
                                    }
                                );
                            }
                            if ( debugEnabled && ! debugRef.current.buttonComputedLogged ) {
                                debugRef.current.buttonComputedLogged = true;
                                multisafepayBlocksDebugGooglePayButtonComputed( logDebug, containerRef.current );
                            }

                            if ( actionsHost ) {
                                syncMultisafepayWalletActionsHostToPlaceOrder( actionsHost, getBlocksCheckoutActionsContainer() );
                            }

                            setBlocksPlaceOrderHiddenByWallet( ! ! actionsHost );
                        } catch ( err ) {
                            logDebug( 'Failed to render Google Pay button:', err );
                            logDebug( '[GooglePay] render failed', multisafepayBlocksSummarizeError( err ) );
                            setError( 'Could not render Google Pay button.' );
                            setBlocksPlaceOrderHiddenByWallet( hasMultisafepayWalletActionsHostAnyContent() );
                        }
                    }
                )
                .catch(
                    ( err ) => {
                        if ( cancelled ) {
                            return;
                        }
                        logDebug( 'isReadyToPay failed:', err );
                        logDebug( '[GooglePay] isReadyToPay failed', multisafepayBlocksSummarizeError( err ) );
                    }
                );
            return () => {
                cancelled = true;
                try {
                    if ( containerRef.current ) {
                        containerRef.current.innerHTML = '';
                    }
                } catch ( e ) {
                    // no-op
                }

                setBlocksPlaceOrderHiddenByWallet( hasMultisafepayWalletActionsHostAnyContent() );
                // When switching away from this wallet method, remove the actions host
                // if it's empty so it can't leave a stale layout that shifts Place order.
                try {
                    const expectedOwner = gateway && gateway.id ? gateway.id : '';
                    setTimeout(
                        () => {
                            cleanupMultisafepayWalletActionsHostIfEmptyForOwner( expectedOwner );
                            setBlocksPlaceOrderHiddenByWallet( hasMultisafepayWalletActionsHostAnyContent() );
                        },
                        60
                    );
                } catch ( e ) {
                    // no-op
                }
            };
        },
        [ gateway && gateway.id, actionsHost ]
    );

    useEffect(
        () => {
            let cancelled = false;
            const sync    = () => {
                if ( cancelled ) {
                    return true;
                }
                const host = ensureMultisafepayWalletActionsHostSlot( gateway && gateway.id ? gateway.id : 'default' );
                if ( host ) {
                    setActionsHost( host );
                    syncMultisafepayWalletActionsHostToPlaceOrder( host, getBlocksCheckoutActionsContainer() );
                    return true;
                }
                return false;
            };
            if ( sync() ) {
                return () => {
                    cancelled = true;
                };
            }
            const stop    = multisafepayBlocksRetryUntil( sync, { intervalMs: 200, maxAttempts: 50, immediate: false } );
            return () => {
                cancelled = true;
                stop();
            };
        },
        []
    );

    useEffect(
        () => {
            if ( ! actionsHost ) {
                return;
            }
            try {
                const connected = ( actionsHost && actionsHost.isConnected ) || ( document.body && document.body.contains( actionsHost ) );
                if ( connected ) {
                    return;
                }
            } catch ( e ) {
                // no-op
            }

            try {
                const ensured = ensureMultisafepayWalletActionsHostSlot( gateway && gateway.id ? gateway.id : 'default' );
                if ( ensured ) {
                    setActionsHost( ensured );
                    syncMultisafepayWalletActionsHostToPlaceOrder( ensured, getBlocksCheckoutActionsContainer() );
                }
            } catch ( e ) {
                // no-op
            }
        },
        [ actionsHost, gateway && gateway.id ]
    );

    useEffect(
        () => {
            if ( ! actionsHost ) {
                return;
            }
            const onResize = () => {
                syncMultisafepayWalletActionsHostToPlaceOrder( actionsHost, getBlocksCheckoutActionsContainer() );
            };
            try {
                window.addEventListener( 'resize', onResize );
            } catch ( e ) {
                // no-op
            }

            onResize();
            return () => {
                try {
                    window.removeEventListener( 'resize', onResize );
                } catch ( e ) {
                    // no-op
                }
            };
        },
        [ actionsHost ]
    );

    useEffect(
        () => {
            return multisafepayBlocksRegisterOnPaymentSubmit(
                eventRegistration,
                async() => {
                    const token   = tokenRef.current;
                    const browser = browserRef.current;
                    if ( ! token ) {
                        logDebug( '[GooglePay] onPaymentSubmit: missing token (customer must click Google Pay button first)' );
                        return { type: 'error', message: 'Please use the Google Pay button.' };
                    }
                    logDebug(
                        '[GooglePay] onPaymentSubmit: injecting paymentMethodData',
                        {
                            tokenLength: typeof token === 'string' ? token.length : 0,
                            browserJsonLength: typeof browser === 'string' ? browser.length : 0,
                        }
                    );
                    const paymentMethodData                            = {};
                    paymentMethodData[ gateway.id + '_payment_token' ] = token;
                    paymentMethodData[ gateway.id + '_browser' ]       = browser;
                    return {
                        type: 'success',
                        meta: {
                            paymentMethodData: paymentMethodData,
                        },
                    };
                }
            );
        },
        [ eventRegistration, gateway && gateway.id ]
    );

    const buttonWrap = createElement( 'div', { className: 'multisafepay-googlepay-direct', ref: containerRef } );
    const errorEl    = error ? createElement( 'div', { className : 'wc-block-components-validation-error', style : { marginTop : '8px' } }, error ) : null;

    const actionsContent = createElement( 'div', null, buttonWrap, errorEl );
if ( actionsHost && typeof createPortal === 'function' ) {
    return createPortal( actionsContent, actionsHost );
}

    return actionsContent;
};
