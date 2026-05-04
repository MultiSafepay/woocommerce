import { select as wpSelect, subscribe as wpSubscribe } from '@wordpress/data';
import { createElement, createPortal, useEffect, useRef, useState } from '@wordpress/element';

import { getCustomerBrowserInfo } from '../browserInfo';
import { multisafepayBlocksSummarizeError, multisafepayBlocksTruncateString } from '../errors';
import { multisafepayBlocksRegisterOnPaymentSubmit } from '../paymentEvents';
import {
    check_apple_pay_availability,
    getBlocksCheckoutActionsContainer,
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
} from './googlePayStyles';

/**
 * Apple Pay "direct" wallet button content for Checkout Blocks.
 *
 * Renders a button in the checkout actions area (outside the payment panel) and,
 * when clicked, performs the Apple Pay flow, stores payment meta into the Blocks
 * payment store, then triggers checkout submission.
 *
 * @param {Object} props
 * @param {Object} props.gateway Gateway data localized from PHP.
 * @param {Object} [props.eventRegistration] Blocks runtime events.
 * @returns {Object|null} Portal element or null.
 */
export const ApplePayDirectContent        = ( props ) => {
    const { eventRegistration, gateway }  = props;
    const containerRef                    = useRef( null );
    const tokenRef                        = useRef( '' );
    const browserRef                      = useRef( '' );
    const cachedTotalPriceRef             = useRef( null );
    const refreshInFlightRef              = useRef( null );
    const refreshDebounceTimerRef         = useRef( 0 );
    const checkoutChangePendingRef        = useRef( false );
    const checkoutResolvingRef            = useRef( false );
    const cartTotalsSignatureRef          = useRef( '' );
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
            availabilityLogged: false,
            buttonRenderedLogged: false,
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
            logDebug( '[ApplePay] refresh totalPrice failed', multisafepayBlocksSummarizeError( err ) );
            return null;
        }
    };

    /**
     * Returns the localized total configured for this gateway.
     *
     * This acts as the safest fallback when no fresher cart total has been read
     * from store state or background refresh.
     *
     * @returns {number} Non-negative total in major currency units.
     */
    const getConfiguredTotalPrice = () => {
        const raw                 = Number( walletConfig && walletConfig.totalPrice ? walletConfig.totalPrice : 0 );
        if ( Number.isNaN( raw ) || raw < 0 ) {
            return 0;
        }
        return raw;
    };

    /**
     * Reads the in-memory total cache.
     *
     * Falls back to configured total when cache is empty.
     *
     * @returns {number} Non-negative total in major currency units.
     */
    const getCachedTotalPrice = () => {
        const cached          = cachedTotalPriceRef.current;
        if ( typeof cached === 'number' && ! Number.isNaN( cached ) && cached >= 0 ) {
            return cached;
        }
        return getConfiguredTotalPrice();
    };

    /**
     * Refreshes the cached total outside the click handler.
     *
     * ApplePaySession must be created synchronously from a user gesture,
     * so this helper should not be awaited before new ApplePaySession(...).
     *
     * @param {string} [source=''] Debug source tag.
     * @returns {Promise<number>} Latest known total.
     */
    const refreshCachedTotalPrice = async( source = '' ) => {
        if ( refreshInFlightRef.current ) {
            return refreshInFlightRef.current;
        }

        refreshInFlightRef.current    = ( async() => {
            const refreshedTotalPrice = await getUpdatedTotalPrice();
            if ( typeof refreshedTotalPrice === 'number' && ! Number.isNaN( refreshedTotalPrice ) ) {
                cachedTotalPriceRef.current = refreshedTotalPrice;
                logDebug(
                    '[ApplePay] total cache refreshed',
                    {
                        source: multisafepayBlocksTruncateString( String( source || 'unknown' ), 30 ),
                        totalPrice: multisafepayBlocksTruncateString( String( refreshedTotalPrice ), 20 ),
                    }
                );
                return refreshedTotalPrice;
            }

            return getCachedTotalPrice();
        } )();

    try {
        return await refreshInFlightRef.current;
    } finally {
        refreshInFlightRef.current = null;
        }
    };

    /**
     * Debounces total cache refresh requests coming from UI/store events.
     *
     * @param {string} [source=''] Debug source tag.
     * @returns {void}
     */
    const scheduleCachedTotalRefresh = ( source = '' ) => {
        if ( refreshDebounceTimerRef.current ) {
            clearTimeout( refreshDebounceTimerRef.current );
        }

        refreshDebounceTimerRef.current         = setTimeout(
            () => {
                refreshDebounceTimerRef.current = 0;
                refreshCachedTotalPrice( source );
            },
            180
        );
    };

    /**
     * Describes checkout resolving state.
     *
     * @typedef {Object} CheckoutResolutionState
     * @property {boolean} known True when resolving state can be queried.
     * @property {boolean} resolving True when any relevant selector is resolving.
     */

    /**
     * Reads resolving state for selectors that influence checkout totals.
     *
     * @returns {CheckoutResolutionState}
     */
    const getCheckoutResolutionState = () => {
        try {
            const coreData = wpSelect( 'core/data' );
            if ( ! coreData || typeof coreData.isResolving !== 'function' ) {
                return { known: false, resolving: false };
            }

            let known     = false;
            let resolving = false;
            const checks  = [
                [ 'wc/store/cart', 'getCartData', [] ],
                [ 'wc/store/cart', 'getCartTotals', [] ],
                [ 'wc/store/cart', 'getShippingRates', [] ],
                [ 'wc/store/checkout', 'getCheckoutData', [] ],
            ];

            for ( const check of checks ) {
                const status = coreData.isResolving( check[ 0 ], check[ 1 ], check[ 2 ] );
                if ( typeof status !== 'boolean' ) {
                    continue;
                }
                known = true;
                if ( status ) {
                    resolving = true;
                    break;
                }
            }

            return { known: known, resolving: resolving };
        } catch ( e ) {
            return { known: false, resolving: false };
        }
    };

    /**
     * Builds a compact totals signature from cart store state.
     *
     * Used to detect meaningful totals changes and avoid unnecessary cache refreshes.
     *
     * @returns {string} Serialized totals signature, or empty string when unavailable.
     */
    const getCartTotalsSignature = () => {
        try {
            const cartStore = wpSelect( 'wc/store/cart' );
            if ( ! cartStore ) {
                return '';
            }

            const cartData = typeof cartStore.getCartData === 'function' ? cartStore.getCartData() : null;
            const totals   = typeof cartStore.getCartTotals === 'function'
                ? cartStore.getCartTotals()
                : ( cartData && cartData.totals );

            if ( ! totals || typeof totals !== 'object' ) {
                return '';
            }

            // Keep explicit null/undefined checks for PHPCS compatibility in this JS file.
            const signatureParts = [
                ( typeof totals.total_price !== 'undefined' && totals.total_price !== null ) ? totals.total_price : '',
                ( typeof totals.total_items !== 'undefined' && totals.total_items !== null ) ? totals.total_items : '',
                ( typeof totals.total_shipping !== 'undefined' && totals.total_shipping !== null ) ? totals.total_shipping : '',
                ( typeof totals.total_tax !== 'undefined' && totals.total_tax !== null ) ? totals.total_tax : '',
                ( typeof totals.currency_code !== 'undefined' && totals.currency_code !== null ) ? totals.currency_code : '',
            ];

            return signatureParts.join( '|' );
        } catch ( e ) {
            return '';
        }
    };

    /**
     * Extracts a normalized major-unit total from a Woo cart totals object.
     *
     * Expects Woo Blocks Store API totals format (snake_case keys).
     *
     * @param {Object|null} totals Totals object from wc/store/cart selectors.
     * @returns {number|null} Total in major currency units, or null when unavailable.
     */
    const extractTotalPriceFromStoreTotals = ( totals ) => {
        if ( ! totals || typeof totals !== 'object' ) {
            return null;
        }

        const currencyMinorUnitRaw = totals.currency_minor_unit;
        const currencyMinorUnit    = Number( currencyMinorUnitRaw );
        const safeMinorUnit        = Number.isNaN( currencyMinorUnit ) || currencyMinorUnit < 0 ? 2 : currencyMinorUnit;
        const divisor              = Math.pow( 10, safeMinorUnit );

        if ( typeof totals.total_price !== 'undefined' && totals.total_price !== null ) {
            const totalMinorRaw = String( totals.total_price ).trim();
            if ( /^-?\d+$/.test( totalMinorRaw ) ) {
                const totalMinor = Number( totalMinorRaw );
                if ( ! Number.isNaN( totalMinor ) && totalMinor >= 0 && divisor > 0 ) {
                    return totalMinor / divisor;
                }
            }

            const fallbackMajor = Number( totalMinorRaw.replace( ',', '.' ) );
            if ( ! Number.isNaN( fallbackMajor ) && fallbackMajor >= 0 ) {
                return fallbackMajor;
            }
        }

        return null;
    };

    /**
     * Reads the current cart total from Woo Blocks store selectors.
     *
     * This method is synchronous and therefore safe to call inside the click
     * handler before creating ApplePaySession.
     *
     * @returns {number|null} Total in major currency units, or null if not available.
     */
    const getBlocksStoreTotalPrice = () => {
        try {
            const cartStore = wpSelect( 'wc/store/cart' );
            if ( ! cartStore ) {
                return null;
            }

            const totalsCandidates = [];

            if ( typeof cartStore.getCartTotals === 'function' ) {
                totalsCandidates.push( cartStore.getCartTotals() );
            }

            if ( typeof cartStore.getCartData === 'function' ) {
                const cartData = cartStore.getCartData();
                if ( cartData && cartData.totals ) {
                    totalsCandidates.push( cartData.totals );
                }
            }

            if ( typeof cartStore.getCart === 'function' ) {
                const cart = cartStore.getCart();
                if ( cart && cart.totals ) {
                    totalsCandidates.push( cart.totals );
                }
            }

            for ( const totals of totalsCandidates ) {
                const totalPrice = extractTotalPriceFromStoreTotals( totals );
                if ( typeof totalPrice === 'number' && ! Number.isNaN( totalPrice ) ) {
                    return totalPrice;
                }
            }
        } catch ( e ) {
            // no-op
        }

        return null;
    };

    /**
     * Queues or performs cache refresh depending on checkout resolving state.
     *
     * @param {string} [source=''] Debug source tag.
     * @returns {void}
     */
    const maybeRefreshAfterCheckoutSettled = ( source = '' ) => {
        const resolutionState              = getCheckoutResolutionState();
        if ( resolutionState.known && resolutionState.resolving ) {
            checkoutChangePendingRef.current = true;
            checkoutResolvingRef.current     = true;
            return;
        }

        checkoutResolvingRef.current = false;
        if ( checkoutChangePendingRef.current ) {
            checkoutChangePendingRef.current = false;
            scheduleCachedTotalRefresh( source || 'checkout:settled' );
        }
    };

    /**
     * Requests an Apple Pay merchant session from the server.
     *
     * Apple requires server-side merchant validation. This helper calls the plugin's
     * AJAX endpoint and normalizes responses that may come back as JSON strings.
     *
     * @param {string} validationURL URL provided by Apple Pay for merchant validation.
     * @param {string} originDomain Origin domain sent to server.
     * @returns {Promise<any>} Parsed merchant session payload.
     */
    const fetchMerchantSession = async( validationURL, originDomain ) => {
        const ajaxUrl          = gateway && gateway.ajax_url ? String( gateway.ajax_url ) : '';
        if ( ! ajaxUrl ) {
            throw new Error( 'Missing AJAX URL.' );
        }

        const data = new URLSearchParams();
        data.append( 'action', 'applepay_direct_validation' );
        data.append( 'validation_url', validationURL );
        data.append( 'origin_domain', originDomain );

        const response = await fetch(
            ajaxUrl,
            {
                method: 'POST',
                body: data,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
            }
        );

        const json = await response.json();

        // Some endpoints return JSON-encoded strings; normalize.
    if ( typeof json === 'string' ) {
        try {
            return JSON.parse( json );
        } catch ( e ) {
            return null;
        }
    }

        return json;
    };

    /**
     * Handles Apple Pay button click.
     *
     * Critical compatibility rule: create ApplePaySession synchronously inside
     * this user gesture handler; avoid awaiting network before session creation.
     *
     * Hybrid behavior: if checkout is resolving but we can read a synchronous,
     * valid store total, continue using that value instead of blocking.
     *
     * @param {Event} e Click event.
     * @returns {void}
     */
    const onClick = ( e ) => {
        if ( e && typeof e.preventDefault === 'function' ) {
            e.preventDefault();
        }
        if ( e && typeof e.stopPropagation === 'function' ) {
            e.stopPropagation();
        }

        setError( '' );

        if ( ! window.ApplePaySession || typeof ApplePaySession.canMakePayments !== 'function' ) {
            setError( 'Apple Pay is not available on this device/browser.' );
            logDebug( '[ApplePay] click: ApplePaySession missing' );
            return;
        }

        if ( ! ApplePaySession.canMakePayments() ) {
            setError( 'Apple Pay is not available.' );
            logDebug( '[ApplePay] click: canMakePayments() false' );
            return;
        }

        const storeTotalPrice    = getBlocksStoreTotalPrice();
        const hasStoreTotalPrice = typeof storeTotalPrice === 'number' && ! Number.isNaN( storeTotalPrice ) && storeTotalPrice >= 0;
        if ( hasStoreTotalPrice ) {
            cachedTotalPriceRef.current = storeTotalPrice;
        }

        const resolutionState  = getCheckoutResolutionState();
        const shouldBlockClick = checkoutChangePendingRef.current || ( resolutionState.known && resolutionState.resolving );
        // Block only when checkout is resolving and no reliable synchronous total
        // can be read from store state for this specific click.
        if ( shouldBlockClick && ! hasStoreTotalPrice ) {
            setError( 'Checkout total is updating. Please try Apple Pay again in a moment.' );
            checkoutChangePendingRef.current = true;
            checkoutResolvingRef.current     = resolutionState.known && resolutionState.resolving;
            maybeRefreshAfterCheckoutSettled( 'click:blocked-waiting-checkout' );
            return;
        }

        try {
            const totalPrice = hasStoreTotalPrice ? storeTotalPrice : getCachedTotalPrice();
            logDebug(
                '[ApplePay] click: using cached totalPrice',
                {
                    source: hasStoreTotalPrice ? 'wc/store/cart' : 'cache',
                    totalPrice: multisafepayBlocksTruncateString( String( totalPrice ), 20 ),
                }
            );

            const paymentRequest = {
                countryCode: String( walletConfig && walletConfig.countryCode ? walletConfig.countryCode : '' ),
                currencyCode: String( walletConfig && walletConfig.currencyCode ? walletConfig.currencyCode : '' ),
                merchantCapabilities: [ 'supports3DS' ],
                supportedNetworks: [ 'amex', 'maestro', 'masterCard', 'visa', 'vPay' ],
                total: {
                    label: String( walletConfig && walletConfig.merchantName ? walletConfig.merchantName : '' ),
                    type: 'final',
                    amount: totalPrice.toFixed( 2 ),
                },
                requiredBillingContactFields: [ 'postalAddress', 'name', 'phone', 'email' ],
                requiredShippingContactFields: [ 'postalAddress', 'name', 'phone', 'email' ],
            };

            // Must be created in the user gesture handler.
            const session = new ApplePaySession( 10, paymentRequest );
            logDebug( '[ApplePay] session created' );

            session.onvalidatemerchant = async( event ) => {
                const failValidation   = ( err ) => {
                    logDebug( '[ApplePay] merchant validation failed', multisafepayBlocksSummarizeError( err ) );
                    try {
                        session.abort();
                    } catch ( e2 ) {
                        // no-op
                    }
                };

                const validationURL = event && event.validationURL ? String( event.validationURL ) : '';
                const originDomain  = window.location && window.location.hostname ? String( window.location.hostname ) : '';
                if ( ! validationURL || ! originDomain ) {
                    failValidation( new Error( 'Missing validation URL or origin domain.' ) );
                    return;
                }

                logDebug( '[ApplePay] onvalidatemerchant' );

                try {
                    const merchantSession = await fetchMerchantSession( validationURL, originDomain );
                    if ( merchantSession && typeof merchantSession === 'object' ) {
                        session.completeMerchantValidation( merchantSession );
                        logDebug( '[ApplePay] merchant validation completed' );
                        return;
                    }

                    failValidation( new Error( 'Invalid merchant session.' ) );
                } catch ( err ) {
                    failValidation( err );
                }
            };

            session.onpaymentauthorized = ( event ) => {
                try {
                    const tokenObj = event && event.payment && event.payment.token ? event.payment.token : null;
                    const token    = tokenObj ? JSON.stringify( tokenObj ) : '';

                    if ( ! token || typeof token !== 'string' ) {
                        setError( 'Apple Pay returned an invalid token.' );
                        logDebug( '[ApplePay] onpaymentauthorized: invalid token' );
                        session.completePayment( ApplePaySession.STATUS_FAILURE );
                        return;
                    }

                    tokenRef.current   = token;
                    browserRef.current = getCustomerBrowserInfo();

                    logDebug(
                        '[ApplePay] onpaymentauthorized: token captured',
                        {
                            tokenLength: token.length,
                            browserJsonLength: typeof browserRef.current === 'string' ? browserRef.current.length : 0,
                        }
                    );

                    // Ensure the correct gateway is the active Blocks payment method,
                    // and set paymentMethodData in the store as a safeguard (survives re-mounts).
                    const methodId = gateway && gateway.id ? gateway.id : '';
                    setBlocksActivePaymentMethodId( methodId );
                    if ( methodId ) {
                        const paymentMethodData                          = {};
                        paymentMethodData[ methodId + '_payment_token' ] = token;
                        paymentMethodData[ methodId + '_browser' ]       = browserRef.current;
                        setBlocksPaymentMethodData( paymentMethodData );
                    }

                    const clicked = triggerBlocksPlaceOrderClick();
                    if ( clicked ) {
                        session.completePayment( ApplePaySession.STATUS_SUCCESS );
                        logDebug( '[ApplePay] onpaymentauthorized: triggered place order' );
                    } else {
                        session.completePayment( ApplePaySession.STATUS_FAILURE );
                        setError( 'Could not submit checkout automatically. Please click Place order.' );
                        logDebug( '[ApplePay] onpaymentauthorized: could not auto-submit' );
                    }
                } catch ( err ) {
                    logDebug( '[ApplePay] onpaymentauthorized failed', multisafepayBlocksSummarizeError( err ) );
                    try {
                        session.completePayment( ApplePaySession.STATUS_FAILURE );
                    } catch ( e2 ) {
                        // no-op
                    }
                }
            };

            session.oncancel = () => {
                logDebug( '[ApplePay] session cancelled' );
            };

            session.begin();
            logDebug( '[ApplePay] session begin()' );
        } catch ( err ) {
            logDebug( '[ApplePay] click: failed to start session', multisafepayBlocksSummarizeError( err ) );
            setError( 'Apple Pay could not be started.' );
        }
    };

    useEffect(
        () => {
            cachedTotalPriceRef.current = getConfiguredTotalPrice();
        },
        [ walletConfig && walletConfig.totalPrice ]
    );

    useEffect(
        () => {
            // Keep cache warm as checkout inputs change.
            const onCheckoutPotentialChange      = () => {
                checkoutChangePendingRef.current = true;
                maybeRefreshAfterCheckoutSettled( 'checkout:dom-change' );
            };
            const form                           = document.querySelector( 'form.wc-block-checkout__form' ) || document.querySelector( '.wc-block-checkout' );
            if ( ! form || typeof form.addEventListener !== 'function' ) {
                return () => {
                    if ( refreshDebounceTimerRef.current ) {
                        clearTimeout( refreshDebounceTimerRef.current );
                        refreshDebounceTimerRef.current = 0;
                    }
                };
            }
            form.addEventListener( 'change', onCheckoutPotentialChange, true );
            form.addEventListener( 'input', onCheckoutPotentialChange, true );
            // Initial refresh after mount to avoid stale localized total.
            scheduleCachedTotalRefresh( 'checkout:init' );
            return () => {
                try {
                    form.removeEventListener( 'change', onCheckoutPotentialChange, true );
                    form.removeEventListener( 'input', onCheckoutPotentialChange, true );
                } catch ( e ) {
                    // no-op
                }
                if ( refreshDebounceTimerRef.current ) {
                    clearTimeout( refreshDebounceTimerRef.current );
                    refreshDebounceTimerRef.current = 0;
                }
            };
        },
        [ gateway && gateway.id ]
    );

    useEffect(
        () => {
            // Refresh cached total right after checkout/cart settling transitions.
            cartTotalsSignatureRef.current = getCartTotalsSignature();
            let frameId                    = 0;
            let timerId                    = 0;
            const runStoreSync             = () => {
                frameId                    = 0;
                timerId                    = 0;
                const resolutionState      = getCheckoutResolutionState();
                const resolvingNow         = resolutionState.known && resolutionState.resolving;
                if ( resolvingNow ) {
                    checkoutChangePendingRef.current = true;
                    checkoutResolvingRef.current     = true;
                    return;
                }
                const justSettled            = checkoutResolvingRef.current && ! resolvingNow;
                checkoutResolvingRef.current = false;
                const signatureNow           = getCartTotalsSignature();
                if ( justSettled ) {
                    checkoutChangePendingRef.current = false;
                    if ( signatureNow ) {
                        cartTotalsSignatureRef.current = signatureNow;
                    }
                    scheduleCachedTotalRefresh( 'checkout:store-settled' );
                    return;
                }
                if ( signatureNow && signatureNow !== cartTotalsSignatureRef.current ) {
                    cartTotalsSignatureRef.current = signatureNow;
                    scheduleCachedTotalRefresh( 'checkout:totals-signature' );
                }
            };
            // wp.data.subscribe fires for all store updates; process at most once per frame.
            const scheduleStoreSync = () => {
                if ( frameId || timerId ) {
                    return;
                }
                if ( typeof window.requestAnimationFrame === 'function' ) {
                    frameId = window.requestAnimationFrame( runStoreSync );
                    return;
                }
                timerId       = window.setTimeout( runStoreSync, 16 );
            };
            const unsubscribe = wpSubscribe( scheduleStoreSync );
            return () => {
                try {
                    unsubscribe();
                } catch ( e ) {
                    // no-op
                }

                if ( frameId && typeof window.cancelAnimationFrame === 'function' ) {
                    window.cancelAnimationFrame( frameId );
                    frameId = 0;
                }

                if ( timerId ) {
                    clearTimeout( timerId );
                    timerId = 0;
                }
            };
        },
        [ gateway && gateway.id ]
    );

    useEffect(
        () => {
            if ( ! gateway || ! walletConfig ) {
                return;
            }
            if ( ! containerRef.current ) {
                return;
            }
            // Render the native Apple Pay button.
            try {
                containerRef.current.innerHTML = '';

                if ( debugEnabled && ! debugRef.current.initLogged ) {
                    debugRef.current.initLogged = true;
                    logDebug(
                        '[ApplePay] init',
                        {
                            env: walletConfig.environment === 'LIVE' ? 'LIVE' : 'TEST',
                            country: String( walletConfig.countryCode || '' ),
                            currency: String( walletConfig.currencyCode || '' ),
                            totalPrice: multisafepayBlocksTruncateString( String( walletConfig.totalPrice || '' ), 20 ),
                            merchantNamePresent: ! ! walletConfig.merchantName,
                        }
                    );
                }

                if ( debugEnabled && ! debugRef.current.availabilityLogged ) {
                    debugRef.current.availabilityLogged = true;
                    const applePaySessionPresent        = ! ! window.ApplePaySession;
                    const canMakePaymentsFnPresent      = applePaySessionPresent && typeof ApplePaySession.canMakePayments === 'function';
                    let canMakePaymentsResult           = false;
                    if ( canMakePaymentsFnPresent ) {
                        try {
                            canMakePaymentsResult = ! ! ApplePaySession.canMakePayments();
                        } catch ( e ) {
                            canMakePaymentsResult = false;
                        }
                    }
                    logDebug(
                        '[ApplePay] availability',
                        {
                            applePaySessionPresent: applePaySessionPresent,
                            canMakePaymentsFnPresent: canMakePaymentsFnPresent,
                            canMakePaymentsResult: canMakePaymentsResult,
                        }
                    );
                }

                if ( ! check_apple_pay_availability() ) {
                    setError( 'Apple Pay is not available on this device/browser.' );
                    logDebug( '[ApplePay] not available (skipping render)' );
                    return;
                }

                const inActions      = ! ! actionsHost;
                const heightPx       = getPreferredWalletButtonHeightPx();
                const maxWidthPx     = getPreferredWalletButtonMaxWidthPx();
                const borderRadiusPx = getPreferredWalletButtonBorderRadiusPx();

                const button        = document.createElement( 'button' );
                button.className    = 'apple-pay-button apple-pay-button-black';
                button.style.cursor = 'pointer';
                button.addEventListener( 'click', onClick );
                button.addEventListener( 'focus', () => scheduleCachedTotalRefresh( 'button:focus' ) );
                button.addEventListener( 'pointerdown', () => scheduleCachedTotalRefresh( 'button:pointerdown' ) );

                // Apply sizing knobs for wallet direct buttons in Blocks.
                // Keep dimensions consistent with Google Pay Direct in Blocks.
                try {
                    if ( inActions ) {
                        containerRef.current.style.setProperty( 'display', 'block', 'important' );
                        if ( maxWidthPx > 0 ) {
                            // Shrink-to-fit so the actions slot stays the button width.
                            containerRef.current.style.setProperty( 'width', maxWidthPx + 'px', 'important' );
                        } else {
                            containerRef.current.style.setProperty( 'width', 'auto', 'important' );
                        }
                        containerRef.current.style.setProperty( 'max-width', '100%', 'important' );
                        containerRef.current.style.setProperty( 'margin-left', '0', 'important' );
                        containerRef.current.style.setProperty( 'margin-right', '0', 'important' );
                    } else {
                        containerRef.current.style.setProperty( 'display', 'flex', 'important' );
                        containerRef.current.style.setProperty( 'justify-content', 'center', 'important' );
                        containerRef.current.style.setProperty( 'align-items', 'center', 'important' );
                        containerRef.current.style.setProperty( 'width', '100%', 'important' );
                        containerRef.current.style.setProperty( 'flex', '0 1 auto', 'important' );
                    }

                    if ( borderRadiusPx > 0 ) {
                        containerRef.current.style.setProperty( 'border-radius', borderRadiusPx + 'px', 'important' );
                        containerRef.current.style.setProperty( 'overflow', 'hidden', 'important' );
                    }
                    if ( ! inActions ) {
                        if ( maxWidthPx > 0 ) {
                            containerRef.current.style.setProperty( 'max-width', maxWidthPx + 'px', 'important' );
                            containerRef.current.style.setProperty( 'margin-left', 'auto', 'important' );
                            containerRef.current.style.setProperty( 'margin-right', 'auto', 'important' );
                        } else {
                            containerRef.current.style.setProperty( 'max-width', '100%', 'important' );
                            containerRef.current.style.setProperty( 'margin-left', '0', 'important' );
                            containerRef.current.style.setProperty( 'margin-right', '0', 'important' );
                        }
                    }
                    if ( heightPx > 0 ) {
                        containerRef.current.style.setProperty( 'height', heightPx + 'px', 'important' );
                        containerRef.current.style.setProperty( 'min-height', heightPx + 'px', 'important' );
                        containerRef.current.style.setProperty( 'max-height', heightPx + 'px', 'important' );
                    }
                } catch ( e ) {
                    // no-op
                }

                try {
                    button.style.setProperty( 'display', 'block', 'important' );
                    button.style.setProperty( 'width', '100%', 'important' );
                    button.style.setProperty( 'max-width', '100%', 'important' );
                    button.style.setProperty( 'min-width', '160px', 'important' );

                    if ( borderRadiusPx > 0 ) {
                        button.style.setProperty( 'border-radius', borderRadiusPx + 'px', 'important' );
                        button.style.setProperty( 'overflow', 'hidden', 'important' );
                    }
                    if ( heightPx > 0 ) {
                        button.style.setProperty( 'height', heightPx + 'px', 'important' );
                        button.style.setProperty( 'min-height', heightPx + 'px', 'important' );
                        button.style.setProperty( 'max-height', heightPx + 'px', 'important' );
                    }
                } catch ( e ) {
                    // no-op
                }

                containerRef.current.appendChild( button );
                if ( debugEnabled && ! debugRef.current.buttonRenderedLogged ) {
                    debugRef.current.buttonRenderedLogged = true;
                    logDebug( '[ApplePay] button rendered', { heightPx: heightPx, maxWidthPx: maxWidthPx, borderRadiusPx: borderRadiusPx } );
                }

                // When the wallet button is shown in the checkout actions area,
                // hide the default Place order button.
                if ( actionsHost ) {
                    syncMultisafepayWalletActionsHostToPlaceOrder( actionsHost, getBlocksCheckoutActionsContainer() );
                }

                setBlocksPlaceOrderHiddenByWallet( ! ! actionsHost );
            } catch ( err ) {
                logDebug( '[ApplePay] render failed', multisafepayBlocksSummarizeError( err ) );
                setError( 'Could not render Apple Pay button.' );
                setBlocksPlaceOrderHiddenByWallet( hasMultisafepayWalletActionsHostAnyContent() );
            }

            return () => {
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

            // Initial sync.
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
                        logDebug( '[ApplePay] onPaymentSubmit: missing token (customer must click Apple Pay button first)' );
                        return { type: 'error', message: 'Please use the Apple Pay button.' };
                    }
                    logDebug(
                        '[ApplePay] onPaymentSubmit: injecting paymentMethodData',
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

    const buttonWrap = createElement( 'div', { className: 'multisafepay-applepay-direct', ref: containerRef } );
    const errorEl    = error ? createElement( 'div', { className : 'wc-block-components-validation-error', style : { marginTop : '8px' } }, error ) : null;

    // Render the wallet-direct button in the checkout actions (Place order) area.
    // Fallback to rendering in the payment method panel if the actions container
    // cannot be found.
    const actionsContent = createElement( 'div', null, buttonWrap, errorEl );
if ( actionsHost && typeof createPortal === 'function' ) {
    return createPortal( actionsContent, actionsHost );
}

    return actionsContent;
};
