import { select as wpSelect, subscribe as wpSubscribe } from '@wordpress/data';
import { createElement, useEffect, useRef, useState } from '@wordpress/element';

import { multisafepayBlocksLogErrorOnce } from '../errors';
import { setBlocksPlaceOrderDisabledByQrOnly, setBlocksPlaceOrderDisabledByLoading } from '../dom/checkout';
import { multisafepayBlocksRegisterOnPaymentSubmit } from '../paymentEvents';

// Cache QR transaction responses per gateway during the page lifetime.
// In classic checkout, the payment component instance is reused (and init is skipped if already mounted),
// which effectively prevents generating a new QR pretransaction when nothing changed.
// Blocks can mount/unmount payment method content when toggling methods, so we need an explicit cache.
const multisafepayBlocksQrTransactionCache = new Map();

/**
 * Blocks Payment Component content.
 *
 * Responsibilities:
 * - Initializes/unmounts the MultiSafepay SDK instance.
 * - For QR-supported gateways, refreshes `orderData` when checkout fields change.
 * - For qr_only, disables Place order to enforce QR flow.
 *
 * @param {Object} props
 * @param {Object} props.gateway Gateway data localized from PHP.
 * @param {Object} [props.eventRegistration] Blocks runtime events.
 * @returns {Object|null}
 */
export const PaymentComponentContent                    = ( props ) => {
    const { eventRegistration, gateway }                = props;
    const containerRef                                  = useRef( null );
    const instanceRef                                   = useRef( null );
    const initGenerationRef                             = useRef( 0 );
    const [ paymentComponent, setPaymentComponent ]     = useState( null );
    const [ isComponentLoading, setIsComponentLoading ] = useState( true );
    const [ errors, setErrors ]                         = useState( [] );
    const errorsSignatureRef                            = useRef( '' );
    const orderIdRef                                    = useRef( null );
    const baseConfig                                    = gateway.payment_component_config || {};
    const [ resolvedConfig, setResolvedConfig ]         = useState( null );
    const [ qrRefreshSignature, setQrRefreshSignature ] = useState( '' );
    const resolvedConfigSignatureRef                    = useRef( '' );
    const ajaxInFlightRef                               = useRef( new Map() );
    const lastRefreshResponseRef                        = useRef( null );
    const refreshFinalizedRef                           = useRef( false );
    const containerId                                   = gateway.id + '_payment_component_container';

    // Build a stable signature, so we only (re)initialize the SDK
    // when the relevant config truly changes.
    // Important: do NOT depend on baseConfig object identity,
    // as it may be recreated between renders even when values are unchanged.
    // This is common in React: parents/selectors often return new object instances.
    const effectiveConfig = resolvedConfig || baseConfig;
    const connectSettings = effectiveConfig &&
        effectiveConfig.orderData &&
        effectiveConfig.orderData.payment_options &&
        effectiveConfig.orderData.payment_options.settings &&
        effectiveConfig.orderData.payment_options.settings.connect
        ? effectiveConfig.orderData.payment_options.settings.connect
        : null;
    const qrConfig        = connectSettings ? connectSettings.qr : null;
    const isQrOnly        = ! ! ( qrConfig && qrConfig.qr_only );
    const qrSignature     = qrConfig ? [
        qrConfig.enabled ? '1' : '0',
        qrConfig.qr_only ? '1' : '0',
        typeof qrConfig.size !== 'undefined' ? String( qrConfig.size ) : '',
    ].join( ':' ) : 'none';
    const initSignature   = [
        gateway.id,
        effectiveConfig && effectiveConfig.env ? effectiveConfig.env : '',
        effectiveConfig && effectiveConfig.api_token ? effectiveConfig.api_token : '',
        effectiveConfig && effectiveConfig.gateway ? effectiveConfig.gateway : '',
        effectiveConfig && effectiveConfig.recurring ? '1' : '0',
        qrSignature,
        ( baseConfig && baseConfig.qr_supported ) ? qrRefreshSignature : '',
    ].join( '|' );

    /**
     * Computes a stable, compact signature for the resolved Payment Component config.
     *
     * Used to avoid unnecessary `setResolvedConfig` calls (which cause re-renders) when the
     * effective config is semantically identical.
     *
     * The signature intentionally includes only the fields that impact SDK init/render and
     * QR behavior.
     *
     * @param {Object} cfg Server-provided config.
     * @returns {string} Stable signature string (or empty string on failure).
     */
    const computeConfigSignature = ( cfg ) => {
        try {
            const env         = cfg && cfg.env ? String( cfg.env ) : '';
            const apiToken    = cfg && cfg.api_token ? String( cfg.api_token ) : '';
            const gatewayCode = cfg && cfg.gateway ? String( cfg.gateway ) : '';
            const recurring   = cfg && cfg.recurring ? '1' : '0';

            const connect         = cfg && cfg.orderData && cfg.orderData.payment_options && cfg.orderData.payment_options.settings && cfg.orderData.payment_options.settings.connect
                ? cfg.orderData.payment_options.settings.connect
                : null;
            const qr              = connect && connect.qr ? connect.qr : null;
            const nextQrSignature = qr ? [
                qr.enabled ? '1' : '0',
                qr.qr_only ? '1' : '0',
                typeof qr.size !== 'undefined' ? String( qr.size ) : '',
            ].join( ':' ) : 'none';

            return [ gateway.id, env, apiToken, gatewayCode, recurring, nextQrSignature ].join( '|' );
        } catch ( e ) {
            return '';
        }
    };

    /**
     * Sets `resolvedConfig` only when a meaningful config change is detected.
     *
     * @param {Object} nextConfig
     * @returns {boolean} True if state was updated.
     */
    const setResolvedConfigIfChanged = ( nextConfig ) => {
        if ( ! nextConfig || typeof nextConfig !== 'object' ) {
            return false;
        }
        const nextSig = computeConfigSignature( nextConfig );
        if ( nextSig && resolvedConfigSignatureRef.current === nextSig ) {
            return false;
        }
        resolvedConfigSignatureRef.current = nextSig;
        setResolvedConfig( nextConfig );
        return true;
    };

    /**
     * Computes a stable signature for the current error set.
     *
     * This keeps error state updates idempotent to avoid re-render churn when the same
     * errors are repeatedly produced by upstream layers.
     *
     * @param {Array<unknown>} nextErrors
     * @returns {string}
     */
    const computeErrorsSignature = ( nextErrors ) => {
        try {
            if ( ! Array.isArray( nextErrors ) || nextErrors.length === 0 ) {
                return '';
            }
            // Signature based on stable, user-visible properties.
            // Keep it compact to avoid large strings.
            const parts = nextErrors.slice( 0, 20 ).map(
                ( err ) => {
                    if ( ! err ) {
                        return '';
                    }
                    if ( typeof err === 'string' ) {
                        return err;
                    }
                    const code    = typeof err.code !== 'undefined' ? String( err.code ) : '';
                    const message = typeof err.message !== 'undefined' ? String( err.message ) : '';
                    return code + ':' + message;
                }
            );
            return parts.join( '|' );
        } catch ( e ) {
            return '';
        }
    };

    /**
     * Sets `errors` only if their stable signature changed.
     *
     * @param {Array<unknown>} nextErrors
     * @returns {boolean} True if state was updated.
     */
    const setErrorsIfChanged = ( nextErrors ) => {
        const sig            = computeErrorsSignature( nextErrors );
        if ( sig === errorsSignatureRef.current ) {
            return false;
        }
        errorsSignatureRef.current = sig;
        setErrors( Array.isArray( nextErrors ) ? nextErrors : [] );
        return true;
    };

    /**
     * Debug logger guarded by `config.debug`.
     *
     * @param {...any} args
     */
    const logger     = ( ...args ) => {
        const config = resolvedConfig || baseConfig;
        if ( ! config || ! config.debug ) {
            return;
        }
        try {
            // eslint-disable-next-line no-console
            console.log( ...args );
        } catch ( e ) {
            // no-op
        }
    };

    /**
     * Reads a minimal set of checkout fields directly from the DOM.
     *
     * In Checkout Blocks, the store state can lag behind typing. This helper is used as a
     * targeted, low-cost fallback to keep `form_data` aligned with the visible inputs.
     *
     * @param {string[]} [requiredKeys=[]] Keys in the `billing_*` / `shipping_*` shape.
     * @returns {Record<string,string>} Map of found values.
     */
    const readBlocksFieldsFromDom = ( requiredKeys = [] ) => {
        const values              = {};

        try {
            const toBlocksId = ( key ) => {
                if ( key.startsWith( 'billing_' ) ) {
                    return key.replace( /^billing_/, 'billing-' );
                }
                if ( key.startsWith( 'shipping_' ) ) {
                    return key.replace( /^shipping_/, 'shipping-' );
                }
                return key;
            };

            const escapeAttributeValue = ( value ) => {
                if ( typeof value !== 'string' ) {
                    return '';
                }
                return value.replace( /\\/g, '\\\\' ).replace( /"/g, '\\"' );
            };

            const getInputValue = ( element, key ) => {
                if ( ! element ) {
                    return '';
                }
                if ( typeof element.checked === 'boolean' && key === 'ship_to_different_address' ) {
                    return element.checked ? '1' : '0';
                }
                if ( typeof element.value === 'string' ) {
                    return element.value;
                }
                return '';
            };

            const findElementForKey  = ( key ) => {
                const id             = toBlocksId( key );
                const escapedName    = escapeAttributeValue( key );
                const escapedId      = escapeAttributeValue( id );
                const selectorByName = escapedName ? '[name="' + escapedName + '"]' : '';
                const selectorById   = escapedId ? '[id="' + escapedId + '"]' : '';

                return (
                    document.getElementById( id ) ||
                    ( selectorByName ? document.querySelector( selectorByName ) : null ) ||
                    ( selectorById ? document.querySelector( selectorById ) : null )
                );
            };

            requiredKeys.forEach(
                ( key ) => {
                    const el = findElementForKey( key );
                    if ( ! el ) {
                        return;
                    }
                    const value = getInputValue( el, key );
                    if ( key === 'ship_to_different_address' ) {
                        values.ship_to_different_address = value;
                        return;
                    }
                    values[ key ] = value;
                }
            );

            // Support both possible IDs for ship-to-different-address toggle.
            if ( requiredKeys.includes( 'ship_to_different_address' ) && ! values.ship_to_different_address ) {
                const toggle = document.getElementById( 'ship-to-different-address' ) || document.getElementById( 'ship_to_different_address' );
                if ( toggle && typeof toggle.checked === 'boolean' ) {
                    values.ship_to_different_address = toggle.checked ? '1' : '0';
                }
            }

            // Contact information fields in Blocks may not be namespaced under billing_*.
            const emailInput = document.querySelector( 'input[type="email"]' );
            if ( emailInput && emailInput.value && ! values.billing_email ) {
                values.billing_email = emailInput.value;
            }
            const telInput = document.querySelector( 'input[type="tel"]' );
            if ( telInput && telInput.value && ! values.billing_phone ) {
                values.billing_phone = telInput.value;
            }
        } catch ( e ) {
            // no-op
        }

        return values;
    };

    /**
     * Builds the exact `form_data` payload sent to server-side refresh/QR endpoints.
     *
     * Note: We prefer store values, but in Blocks store updates can lag behind typing;
     * we apply DOM values to keep the payload in sync.
     *
     * @param {string} [source=''] Debug context tag.
     * @returns {string}
     */
    const buildFormData = ( source = '' ) => {
        const params    = new URLSearchParams();
        params.set( 'payment_method', gateway.id );

        const billingRequiredForQr = [
            'billing_first_name',
            'billing_last_name',
            'billing_address_1',
            'billing_city',
            'billing_postcode',
            'billing_country',
            'billing_email',
            'billing_phone',
        ];

        const checkoutStore    = wpSelect( 'wc/store/checkout' );
        const cartStore        = wpSelect( 'wc/store/cart' );
        const checkoutData     = checkoutStore && checkoutStore.getCheckoutData ? checkoutStore.getCheckoutData() : {};
        const customerData     = checkoutStore && checkoutStore.getCustomerData ? checkoutStore.getCustomerData() : {};
        const cartCustomerData = cartStore && cartStore.getCustomerData ? cartStore.getCustomerData() : {};

        const pickAddress = ( candidates ) => {
            if ( ! Array.isArray( candidates ) ) {
                return {};
            }
            const candidatesLength = candidates.length;
            for ( let i = 0; i < candidatesLength; i++ ) {
                const candidate = candidates[ i ];
                if ( candidate && typeof candidate === 'object' && Object.keys( candidate ).length > 0 ) {
                    return candidate;
                }
            }
            return {};
        };

        const asBoolean = ( value ) => {
            if ( typeof value === 'boolean' ) {
                return value;
            }
            if ( typeof value === 'string' ) {
                const normalized = value.trim().toLowerCase();
                return normalized === '1' || normalized === 'true' || normalized === 'yes' || normalized === 'on';
            }
            if ( typeof value === 'number' ) {
                return value === 1;
            }
            return false;
        };

        const billing       = pickAddress(
            [
                customerData && customerData.billingAddress,
                customerData && customerData.billing_address,
                checkoutData && checkoutData.billingAddress,
                checkoutData && checkoutData.billing_address,
                cartCustomerData && cartCustomerData.billingAddress,
                cartCustomerData && cartCustomerData.billing_address,
                checkoutStore && checkoutStore.getBillingAddress ? checkoutStore.getBillingAddress() : null,
                cartStore && cartStore.getBillingAddress ? cartStore.getBillingAddress() : null,
            ]
        );
        const shipping      = pickAddress(
            [
                customerData && customerData.shippingAddress,
                customerData && customerData.shipping_address,
                checkoutData && checkoutData.shippingAddress,
                checkoutData && checkoutData.shipping_address,
                cartCustomerData && cartCustomerData.shippingAddress,
                cartCustomerData && cartCustomerData.shipping_address,
                checkoutStore && checkoutStore.getShippingAddress ? checkoutStore.getShippingAddress() : null,
                cartStore && cartStore.getShippingAddress ? cartStore.getShippingAddress() : null,
            ]
        );
        let shipToDifferent = asBoolean( customerData && customerData.shipToDifferentAddress )
            || asBoolean( customerData && customerData.ship_to_different_address )
            || asBoolean( checkoutData && checkoutData.shipToDifferentAddress )
            || asBoolean( checkoutData && checkoutData.ship_to_different_address )
            || asBoolean( cartCustomerData && cartCustomerData.shipToDifferentAddress )
            || asBoolean( cartCustomerData && cartCustomerData.ship_to_different_address )
            ? '1'
            : '0';

        // Mirror server-side validation in QrCheckoutManager:
        // - Billing required fields always.
        // - If shipping to a different address, require shipping_* equivalents of billing required
        // (excluding email/phone) as required fields too.
        const shippingRequiredForQr = billingRequiredForQr
            .filter( ( field ) => field !== 'billing_email' && field !== 'billing_phone' )
            .map( ( field ) => field.replace( /^billing_/, 'shipping_' ) );
        let requiredForQr           = shipToDifferent === '1'
            ? billingRequiredForQr.concat( shippingRequiredForQr )
            : billingRequiredForQr;

        const getValue = ( obj, key ) => ( obj && obj[ key ] ) ? obj[ key ] : '';

        const mapAddress = ( prefix, address ) => {
            if ( ! address ) {
                return;
            }

            params.set( prefix + '_first_name', getValue( address, 'first_name' ) );
            params.set( prefix + '_last_name', getValue( address, 'last_name' ) );
            params.set( prefix + '_company', getValue( address, 'company' ) );
            params.set( prefix + '_address_1', getValue( address, 'address_1' ) );
            params.set( prefix + '_address_2', getValue( address, 'address_2' ) );
            params.set( prefix + '_city', getValue( address, 'city' ) );
            params.set( prefix + '_state', getValue( address, 'state' ) );
            params.set( prefix + '_postcode', getValue( address, 'postcode' ) );
            params.set( prefix + '_country', getValue( address, 'country' ) );
            params.set( prefix + '_email', getValue( address, 'email' ) );
            params.set( prefix + '_phone', getValue( address, 'phone' ) );
        };

        mapAddress( 'billing', billing );

        // Woo Blocks may store email/phone outside the billingAddress object.
        if ( ! params.get( 'billing_email' ) ) {
            const emailFallback = ( customerData && customerData.email ) || ( checkoutData && checkoutData.email ) || ( cartCustomerData && cartCustomerData.email ) || '';
            if ( emailFallback ) {
                params.set( 'billing_email', emailFallback );
            }
        }
        if ( ! params.get( 'billing_phone' ) ) {
            const phoneFallback = ( customerData && customerData.phone ) || ( checkoutData && checkoutData.phone ) || ( cartCustomerData && cartCustomerData.phone ) || '';
            if ( phoneFallback ) {
                params.set( 'billing_phone', phoneFallback );
            }
        }

        // DOM assistance for block-based checkout.
        // Important: Blocks store updates may lag behind typing; use DOM values to keep form_data in sync.
        // Use targeted lookups (id/name) to avoid expensive full-DOM scans.
        const domInitialValues = readBlocksFieldsFromDom( billingRequiredForQr.concat( [ 'ship_to_different_address' ] ) );
        if ( domInitialValues && typeof domInitialValues.ship_to_different_address !== 'undefined' && domInitialValues.ship_to_different_address !== '' ) {
            shipToDifferent = domInitialValues.ship_to_different_address === '1' ? '1' : '0';
            requiredForQr   = shipToDifferent === '1'
                ? billingRequiredForQr.concat( shippingRequiredForQr )
                : billingRequiredForQr;
        }

        if ( shipToDifferent === '1' ) {
            params.set( 'ship_to_different_address', '1' );
            mapAddress( 'shipping', shipping );
        }

        if ( cartStore && cartStore.getShippingRates ) {
            const shippingRates = cartStore.getShippingRates();
            if ( Array.isArray( shippingRates ) && shippingRates.length > 0 && shippingRates[0].shipping_rates ) {
                const chosenRate = shippingRates[0].shipping_rates.find( rate => rate.selected ) || shippingRates[0].shipping_rates[0];
                if ( chosenRate && chosenRate.rate_id ) {
                    params.set( 'shipping_method', chosenRate.rate_id );
                }
            }
        }

        if ( baseConfig && baseConfig.qr_supported ) {
            const domKeys    = requiredForQr.concat( [ 'ship_to_different_address' ] );
            const domValues  = readBlocksFieldsFromDom( domKeys );
            const objectKeys = Object.keys( domValues );
            if ( objectKeys.length > 0 ) {
                const shouldLogDom = ! ! ( source && source !== 'qr_event_driven_signature' );
                if ( shouldLogDom ) {
                    logger( 'Blocks: Applying DOM values for form_data (' + source + '):', objectKeys );
                }
                objectKeys.forEach(
                    ( key ) => {
                        if ( typeof domValues[ key ] !== 'undefined' && domValues[ key ] !== '' ) {
                            params.set( key, String( domValues[ key ] ) );
                        }
                    }
                );
            }
        }

        // Debug helper: check missing required fields for QR validation (matches server-side QrCheckoutManager).
        const missing = requiredForQr.filter( key => ! params.get( key ) );
        if ( missing.length > 0 ) {
            logger( 'Blocks: Missing required checkout fields for QR:', missing );
        }

        return params.toString();
    };

    /**
     * Detects AbortController-related errors across browsers.
     *
     * @param {any} error
     * @returns {boolean}
     */
    const isAbortError = ( error ) => {
        try {
            return ! ! ( error && ( error.name === 'AbortError' || error.code === 20 ) );
        } catch ( e ) {
            return false;
        }
    };

    useEffect(
        () => {
            return () => {
                // Abort any in-flight AJAX requests when switching payment methods/unmounting.
                try {
                    const map = ajaxInFlightRef.current;
                    if ( map && map.forEach ) {
                        map.forEach(
                            ( entry ) => {
                                try {
                                    if ( entry && entry.controller && typeof entry.controller.abort === 'function' ) {
                                        entry.controller.abort();
                                    }
                                } catch ( e ) {
                                    // no-op
                                }
                            }
                        );
                        map.clear();
                    }
                } catch ( e ) {
                    // no-op
                }
            };
        },
        []
    );

    /**
     * Performs a WP AJAX request with optional in-flight deduplication + AbortController.
     *
     * @param {Object} data
     * @param {Object} [options]
     * @param {boolean} [options.dedupe=true]
     * @returns {Promise<any>}
     */
    const requestAjax = ( data, { dedupe = true } = {} ) => {
        if ( ! baseConfig.ajax_url ) {
            return Promise.reject( new Error( 'Missing AJAX URL.' ) );
        }

        const body       = new URLSearchParams( data );
        const bodyString = body.toString();

        // Dedupe identical requests while they are in-flight.
        let dedupeKey = '';
        try {
            const action    = data && data.action ? String( data.action ) : '';
            const gatewayId = data && data.gateway_id ? String( data.gateway_id ) : '';
            dedupeKey       = ( dedupe && action ) ? [ String( baseConfig.ajax_url ), action, gatewayId, bodyString ].join( '|' ) : '';
        } catch ( e ) {
            dedupeKey = '';
        }

        if ( dedupeKey ) {
            const existing = ajaxInFlightRef.current.get( dedupeKey );
            if ( existing && existing.promise ) {
                return existing.promise;
            }
        }

        const controller     = typeof AbortController !== 'undefined' ? new AbortController() : null;
        const requestOptions = {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: bodyString,
            credentials: 'same-origin',
        };

        if ( controller && controller.signal ) {
            requestOptions.signal = controller.signal;
        }
        const request = fetch( baseConfig.ajax_url, requestOptions );

        const promise = request.then( ( response ) => response.json() );

        if ( dedupeKey ) {
            ajaxInFlightRef.current.set( dedupeKey, { controller, promise } );
        }

        const cleanup = () => {
            if ( ! dedupeKey ) {
                return;
            }
            try {
                const current = ajaxInFlightRef.current.get( dedupeKey );
                if ( current && current.promise === promise ) {
                    ajaxInFlightRef.current.delete( dedupeKey );
                }
            } catch ( e ) {
                // no-op
            }
        };

        promise.then( cleanup ).catch( cleanup );
        return promise;
    };

    /**
     * Fetches the redirect URL used after QR order placement.
     *
     * @param {number|string} orderId
     * @param {string} [qrStatus=''] Optional QR status used by redirect handling.
     * @returns {Promise<any>}
     */
    const getQrOrderRedirectUrl = ( orderId, qrStatus = '' ) => {
        const config            = resolvedConfig || baseConfig;
        const data              = {
            nonce: config.nonce,
            action: 'get_qr_order_redirect_url',
            gateway_id: gateway.id,
            order_id: orderId,
            qr_status: qrStatus,
        };

        return requestAjax( data );
    };

    /**
     * Requests an updated Payment Component config from the server.
     *
     * @param {string} [formDataOverride=''] Optional precomputed `form_data` to avoid recomputation.
     * @returns {Promise<any>}
     */
    const refreshPaymentComponentConfig = ( formDataOverride = '' ) => {
        const config                    = resolvedConfig || baseConfig;
        const data                      = {
            nonce: config.nonce,
            action: 'refresh_payment_component_config',
            gateway_id: gateway.id,
            form_data: formDataOverride || buildFormData( 'refresh_payment_component_config' ),
        };
        return requestAjax( data );
    };

    useEffect(
        () => {
            if ( ! baseConfig || ! baseConfig.nonce || ! baseConfig.ajax_url ) {
                return;
            }
            // For QR methods we resolve (and refresh) config so connect.qr appears once checkout fields are valid.
            // For non-QR methods we usually don't need to refresh, but in Blocks we may intentionally ship an
            // api_token-less base config to avoid blocking initial page render on remote API calls.
            if ( ! baseConfig.qr_supported ) {
                const hasApiToken = ( typeof baseConfig.api_token === 'string' ) && baseConfig.api_token.trim().length > 0;
                if ( hasApiToken ) {
                    return;
                }

                let isCanceled = false;
                refreshPaymentComponentConfig().then(
                    ( response ) => {
                        if ( isCanceled ) {
                            return;
                        }
                        if ( response && response.orderData ) {
                            setResolvedConfigIfChanged( response );
                        }
                    }
                ).catch(
                    ( error ) => {
                        if ( isCanceled ) {
                            return;
                        }
                        if ( isAbortError( error ) ) {
                            return;
                        }
                        multisafepayBlocksLogErrorOnce( ! ! ( baseConfig && baseConfig.debug ), 'Blocks: Failed to refresh payment component config.', error );
                    }
                );

                return () => {
                    isCanceled = true;
                };
            }

            // Reset per-gateway refresh state.
            lastRefreshResponseRef.current     = null;
            refreshFinalizedRef.current        = false;
            resolvedConfigSignatureRef.current = '';
            let isCanceled                     = false;
            let inFlight                       = false;
            let pendingRefresh                 = false;
            let debounceId                     = null;
            let unsubscribe                    = null;
            let lastFormDataSignature          = '';
            let latestRequestedSignature       = '';
            let latestRequestedFormData        = '';
            const unsubscribeNow               = () => {
                try {
                    if ( unsubscribe ) {
                        unsubscribe();
                    }
                } catch ( e ) {
                    // no-op
                }
                unsubscribe  = null;
            };
            const tryRefresh = () => {
                if ( isCanceled || refreshFinalizedRef.current || inFlight ) {
                    return;
                }
                inFlight               = true;
                const requestSignature = latestRequestedSignature;
                refreshPaymentComponentConfig( latestRequestedFormData ).then(
                    ( response ) => {
                        inFlight       = false;
                        if ( isCanceled ) {
                            return;
                        }
                        if ( response && response.orderData ) {
                            lastRefreshResponseRef.current = response;
                        }
                        if ( baseConfig && baseConfig.debug ) {
                            const qr = response && response.orderData && response.orderData.payment_options && response.orderData.payment_options.settings && response.orderData.payment_options.settings.connect ? response.orderData.payment_options.settings.connect.qr : null;
                            logger( 'Blocks: refresh_payment_component_config QR config:', qr );
                        }
                        if ( response && response.orderData ) {
                            // For QR, we must allow orderData changes to re-init the SDK.
                            // initSignature includes qrRefreshSignature, which we update only after a successful refresh.
                            setResolvedConfig( response );
                            if ( requestSignature && requestSignature !== qrRefreshSignature ) {
                                setQrRefreshSignature( requestSignature );
                            }
                        }
                        if ( pendingRefresh && ! refreshFinalizedRef.current ) {
                            pendingRefresh = false;
                            scheduleRefresh( { immediate: true } );
                        }
                    }
                ).catch(
                    ( error ) => {
                        inFlight = false;
                        if ( isCanceled ) {
                            return;
                        }
                        if ( pendingRefresh && ! refreshFinalizedRef.current ) {
                            pendingRefresh = false;
                            scheduleRefresh( { immediate: true } );
                        }
                        if ( isAbortError( error ) ) {
                            return;
                        }
                        multisafepayBlocksLogErrorOnce( ! ! ( baseConfig && baseConfig.debug ), 'Blocks: Failed to refresh payment component config.', error );
                    }
                );
            };
            const scheduleRefresh = ( { immediate = false } = {} ) => {
                if ( isCanceled || refreshFinalizedRef.current ) {
                    return;
                }
                try {
                    if ( debounceId ) {
                        clearTimeout( debounceId );
                    }
                } catch ( e ) {
                    // no-op
                }

                const delayMs      = immediate ? 0 : 450;
                debounceId         = setTimeout(
                    () => {
                        debounceId = null;
                        if ( isCanceled || refreshFinalizedRef.current ) {
                            return;
                        }
                        // Keep server-side validation as the source of truth.
                        // We only detect "something changed" by comparing the exact payload we send.
                        let nextSignature;
                        try {
                            nextSignature = buildFormData( 'qr_event_driven_signature' );
                        } catch ( e ) {
                            nextSignature = '';
                        }

                        if ( nextSignature && nextSignature === lastFormDataSignature ) {
                            return;
                        }
                        lastFormDataSignature    = nextSignature;
                        latestRequestedSignature = nextSignature;
                        latestRequestedFormData  = nextSignature;
                        if ( inFlight ) {
                            pendingRefresh = true;
                            return;
                        }
                        tryRefresh();
                    },
                    delayMs
                );
            };
            const onStoresChanged = () => {
                if ( isCanceled || refreshFinalizedRef.current ) {
                    return;
                }
                // Keep subscribe callback cheap; debounce does the expensive signature build.
                scheduleRefresh();
            };
            // Subscribe to check out/cart updates to trigger refresh when the customer changes data.
            try {
                unsubscribe = wpSubscribe( onStoresChanged );
            } catch ( e ) {
                unsubscribe = null;
            }

            // DOM listeners as a fallback: store updates may not fire on every keystroke.
            let checkoutForm = null;
            try {
                checkoutForm = document.querySelector( 'form.wc-block-checkout__form' );
                if ( checkoutForm && checkoutForm.addEventListener ) {
                    checkoutForm.addEventListener( 'input', onStoresChanged, true );
                    checkoutForm.addEventListener( 'change', onStoresChanged, true );
                }
            } catch ( e ) {
                checkoutForm = null;
            }

            // Initial attempt: resolve the config via AJAX so QR (and qr_only) settings are included.
            scheduleRefresh( { immediate: true } );
            return () => {
                isCanceled = true;
                try {
                    if ( debounceId ) {
                        clearTimeout( debounceId );
                    }
                } catch ( e ) {
                    // no-op
                }
                debounceId = null;
                try {
                    if ( checkoutForm && checkoutForm.removeEventListener ) {
                        checkoutForm.removeEventListener( 'input', onStoresChanged, true );
                        checkoutForm.removeEventListener( 'change', onStoresChanged, true );
                    }
                } catch ( e ) {
                    // no-op
                }
                checkoutForm = null;
                unsubscribeNow();
            };
        },
        // Re-resolve when the base config changes (e.g., selected gateway).
        [ baseConfig && baseConfig.nonce, baseConfig && baseConfig.ajax_url, gateway.id ]
    );

    useEffect(
        () => {
            const config = effectiveConfig;
            // If QR is supported, wait for the refreshed config so we don't init twice
            // (once without connect.qr and then again with connect.qr).
            if ( baseConfig && baseConfig.qr_supported && ! resolvedConfig ) {
                return;
            }

            if ( ! containerRef.current || ! config.api_token || ! window.MultiSafepay ) {
                return;
            }

            // Tear down any previous instance to avoid duplicate event handlers.
            try {
                if ( instanceRef.current ) {
                    if ( typeof instanceRef.current.destroy === 'function' ) {
                        instanceRef.current.destroy();
                    } else if ( typeof instanceRef.current.unmount === 'function' ) {
                        instanceRef.current.unmount();
                    }
                }
            } catch ( e ) {
                // no-op
            }
            instanceRef.current = null;
            setPaymentComponent( null );
            setIsComponentLoading( true );
            setBlocksPlaceOrderDisabledByLoading( true );
            setErrorsIfChanged( [] );
            const instanceConfig      = {
                env: config.env,
                apiToken: config.api_token,
                order: config.orderData,
                recurring: config.recurring,
            };
            const instance            = new window.MultiSafepay( instanceConfig );
            const generation          = initGenerationRef.current + 1;
            initGenerationRef.current = generation;
            const initConfig          = {
                container: '#' + containerId,
                gateway: config.gateway,
                onLoad: ( state ) => {
                    if ( generation !== initGenerationRef.current ) {
                        return;
                    }
                    logger( 'Blocks: onLoad:', state );
                },
                onError: ( state ) => {
                    if ( generation !== initGenerationRef.current ) {
                        return;
                    }
                    logger( 'Blocks: onError:', state );
                    setIsComponentLoading( false );
                    setBlocksPlaceOrderDisabledByLoading( false );
                    if ( state && state.errors ) {
                        setErrorsIfChanged( state.errors.errors || [] );
                    }
                },
                onValidation: ( state ) => {
                    if ( generation !== initGenerationRef.current ) {
                        return;
                    }
                    logger( 'Blocks: onValidation:', state );
                },
                onGetQR: ( state ) => {
                    if ( generation !== initGenerationRef.current ) {
                        return;
                    }
                    logger( 'Blocks: onGetQR:', state );
                    if ( ! config.qr_supported || ! state || ! state.orderData ) {
                        return;
                    }
                    if ( state.orderData.payment_data && state.orderData.payment_data.payload ) {
                        // Cache key strategy:
                        // - We intentionally do NOT subscribe to checkout-store changes to invalidate the cache proactively.
                        // - Instead, we derive a signature from the latest checkout data (form_data) plus order amount/currency/env.
                        // This signature is recomputed each time onGetQR fires, so if the customer changes checkout details
                        // (address, email, shipping, totals, etc.), the signature changes, and a new pretransaction is created.
                        const formData  = buildFormData( 'qr_cache_signature' );
                        const amount    = typeof state.orderData.amount !== 'undefined' ? String( state.orderData.amount ) : '';
                        const currency  = typeof state.orderData.currency !== 'undefined' ? String( state.orderData.currency ) : '';
                        const env       = config && config.env ? String( config.env ) : '';
                        const signature = [ gateway.id, env, currency, amount, formData ].join( '|' );

                        const cached = multisafepayBlocksQrTransactionCache.get( gateway.id );
                        const now    = Date.now();
                        const ttlMs  = 5 * 60 * 1000;
                        if ( cached && cached.signature === signature && cached.response && ( now - cached.ts ) < ttlMs ) {
                            logger( 'Blocks: Reusing cached QR transaction (no checkout changes detected).' );
                            instance.setQR( { order: cached.response } );
                            if ( cached.response.order_id ) {
                                orderIdRef.current = cached.response.order_id;
                            }
                            return;
                        }

                        const payload  = state.orderData.payment_data.payload;
                        const ajaxData = {
                            nonce: ( resolvedConfig || baseConfig ).nonce,
                            action: 'set_multisafepay_qr_code_transaction',
                            gateway_id: gateway.id,
                            payload: payload,
                            form_data: formData,
                        };

                        requestAjax( ajaxData ).then(
                            ( response ) => {
                                logger( 'Blocks: QR transaction response:', response );
                                if ( response ) {
                                    // Cache only successful responses.
                                    if ( response.order_id ) {
                                        multisafepayBlocksQrTransactionCache.set(
                                            gateway.id,
                                            {
                                                signature: signature,
                                                response: response,
                                                ts: Date.now(),
                                            }
                                        );
                                        orderIdRef.current = response.order_id;
                                    }

                                    instance.setQR( { order: response } );
                                }
                            }
                        ).catch(
                            ( error ) => {
                                if ( isAbortError( error ) ) {
                                    return;
                                }
                                multisafepayBlocksLogErrorOnce( ! ! ( baseConfig && baseConfig.debug ), 'Blocks: Failed to set QR transaction.', error );
                            }
                        );
                    }
                },
                onEvent: ( state ) => {
                    if ( generation !== initGenerationRef.current ) {
                        return;
                    }
                    logger( 'Blocks: onEvent:', state );
                    if ( ! config.qr_supported || ! state || ! state.type ) {
                        return;
                    }
                    if ( ( state.type === 'check_status' ) && state.success && state.data && state.data.qr_status ) {
                        if ( orderIdRef.current ) {
                            switch ( state.data.qr_status ) {
                                case 'completed':
                                case 'declined':
                                case 'cancelled':
                                    getQrOrderRedirectUrl( orderIdRef.current, state.data.qr_status ).then(
                                        ( response ) => {
                                            if ( response && response.success && response.redirect_url ) {
                                                window.location.href = response.redirect_url;
                                            }
                                        }
                                    );
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                },
            };
            try {
                if ( containerRef.current ) {
                    containerRef.current.innerHTML = '';
                }
            } catch ( e ) {
                // no-op
            }

            instance.init( 'payment', initConfig );
            instanceRef.current = instance;
            setPaymentComponent( instance );
            // Fallback: use a MutationObserver to detect when the SDK has rendered its
            // content (iframe, form fields, etc.) into the container.  This covers gateways
            // with tokenization/recurring where onLoad only fires after user interaction.
            let observer         = null;
            let fallbackTimer    = null;
            const dismissLoading = () => {
                if ( generation !== initGenerationRef.current ) {
                    return;
                }
                setIsComponentLoading( false );
                setBlocksPlaceOrderDisabledByLoading( false );
            };
            const hasRenderedContent = ( node ) => {
                if ( ! node ) {
                    return false;
                }
                // An iframe means the SDK has mounted its secure fields.
                if ( node.querySelector && node.querySelector( 'iframe' ) ) {
                    return true;
                }
                // At least one meaningful child element (skip empty text nodes).
                if ( node.children && node.children.length > 0 ) {
                    return true;
                }
                return false;
            };
            try {
                const target = containerRef.current;
                if ( target && typeof MutationObserver !== 'undefined' ) {
                    observer = new MutationObserver(
                        () => {
                            if ( hasRenderedContent( target ) ) {
                                dismissLoading();
                                if ( observer ) {
                                    observer.disconnect();
                                    observer = null;
                                }
                                if ( fallbackTimer ) {
                                    clearTimeout( fallbackTimer );
                                    fallbackTimer = null;
                                }
                            }
                    }
                        );
                    observer.observe( target, { childList: true, subtree: true } );

                    // If content was already injected synchronously by init().
                    if ( hasRenderedContent( target ) ) {
                        dismissLoading();
                        observer.disconnect();
                        observer = null;
                    }
                }
            } catch ( e ) {
                // no-op
            }

            // Hard fallback: dismiss after 5 s no matter what, so the UI never gets stuck.
            fallbackTimer         = setTimeout(
                () => {
                    fallbackTimer = null;
                    dismissLoading();
                    if ( observer ) {
                        observer.disconnect();
                        observer = null;
                    }
                },
                5000
            );
            return () => {
                setBlocksPlaceOrderDisabledByLoading( false );
                if ( observer ) {
                    try {
                        observer.disconnect();
                    } catch ( e ) {
                        // no-op
                    }
                    observer = null;
                }
                if ( fallbackTimer ) {
                    clearTimeout( fallbackTimer );
                    fallbackTimer = null;
                }
                try {
                    if ( instanceRef.current ) {
                        if ( typeof instanceRef.current.destroy === 'function' ) {
                            instanceRef.current.destroy();
                        } else if ( typeof instanceRef.current.unmount === 'function' ) {
                            instanceRef.current.unmount();
                        }
                    }
                } catch ( e ) {
                    // no-op
                }
                instanceRef.current = null;
            };
        },
        [ initSignature ]
    );

    useEffect(
        () => {
            // If QR is the only allowed flow, prevent manual checkout submission.
            // This is scoped to the lifetime of this payment method content.
            setBlocksPlaceOrderDisabledByQrOnly( isQrOnly );
            return () => {
                setBlocksPlaceOrderDisabledByQrOnly( false );
            };
        },
        [ isQrOnly ]
    );

    useEffect(
        () => {
            return multisafepayBlocksRegisterOnPaymentSubmit(
                eventRegistration,
                async() => {
                    if ( ! paymentComponent ) {
                        return { type: 'error', message: 'Payment component is not ready.' };
                    }
                    if ( paymentComponent.hasErrors && paymentComponent.hasErrors() ) {
                        const pcErrors     = paymentComponent.getErrors ? paymentComponent.getErrors() : null;
                        const firstMessage = pcErrors && pcErrors.errors && pcErrors.errors[0] ? pcErrors.errors[0].message : 'Invalid payment details.';
                        return { type: 'error', message: firstMessage };
                    }
                    const paymentData = paymentComponent.getPaymentData ? paymentComponent.getPaymentData() : null;
                    if ( ! paymentData || ! paymentData.payload ) {
                        return { type: 'error', message: 'Payment details are required.' };
                    }
                    const paymentMethodData                                        = {};
                    paymentMethodData[ gateway.id + '_payment_component_payload' ] = paymentData.payload;
                    paymentMethodData[ gateway.id + '_payment_component_tokenize' ] = paymentData.tokenize ? '1' : '0';
                    return {
                        type: 'success',
                        meta: {
                            paymentMethodData: paymentMethodData,
                        },
                    };
                }
            );
        },
        [ eventRegistration, paymentComponent, gateway.id ]
    );

    const descriptionElement = gateway.description ? createElement( 'p', null, gateway.description ) : null;
    const containerElement   = createElement(
        'div',
        { style: { position: 'relative', minHeight: isComponentLoading ? '80px' : undefined } },
        isComponentLoading ? createElement(
            'div',
            { className: 'multisafepay-payment-component-loader-wrapper' },
            createElement( 'div', { className: 'multisafepay-payment-component-loader' } )
        ) : null,
        createElement( 'div', { id: containerId, ref: containerRef, className: 'multisafepay-payment-component' } )
    );
    let errorElement         = null;

if ( errors && errors.length > 0 ) {
    const errorItems = errors.map( ( error, index ) => createElement( 'div', { key: index }, error.message ) );
    errorElement     = createElement( 'div', { className: 'wc-block-components-validation-error' }, errorItems );
}

    return createElement( 'div', null, descriptionElement, containerElement, errorElement );
};
