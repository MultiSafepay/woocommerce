import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { dispatch as wpDispatch } from '@wordpress/data';
import { createElement } from '@wordpress/element';

import { check_apple_pay_availability } from '../dom/checkout';
import { multisafepayBlocksRetryUntil } from '../dom/retry';
import { multisafepayBlocksLogErrorOnce } from '../errors';
import { ApplePayDirectContent } from '../wallet/applePayDirect';
import { GooglePayDirectContent } from '../wallet/googlePayDirect';
import { PaymentComponentContent } from '../payment-component/paymentComponentContent';

/**
 * @file Registers MultiSafepay gateways into WooCommerce Blocks.
 *
 * This module is loaded in the single Blocks bundle and registers payment methods once
 * the Woo Blocks registry script and `window.multisafepay_gateways` are available.
 */

const createOptions = ( gateway ) => {
    /**
     * Maps a gateway data object into WC Blocks `registerPaymentMethod` options.
     *
     * @param {Object} gateway
     * @returns {Object}
     */
    const labelElements = [];

    // Use a component function so Woo Blocks can pass runtime props
    // like `eventRegistration` into our content.
    /**
     * @type {(props?: any) => any}
     */
    let Content = ( _props ) => createElement( 'p', null, gateway.description );

    if ( gateway && gateway.gateway_code === 'GOOGLEPAY' && gateway.direct_button_enabled ) {
        Content = ( props ) => createElement( GooglePayDirectContent, { ...props, gateway: gateway } );
    } else if ( gateway && gateway.gateway_code === 'APPLEPAY' && gateway.direct_button_enabled ) {
        Content = ( props ) => createElement( ApplePayDirectContent, { ...props, gateway: gateway } );
    } else if ( gateway && gateway.has_payment_component ) {
        Content = ( props ) => createElement( PaymentComponentContent, { ...props, gateway: gateway } );
    }

    if ( gateway.icon ) {
        const iconElement = createElement(
            'img',
            {
                src: gateway.icon,
                alt: gateway.title,
                style: { height: '24px', width: 'auto', marginRight: '8px' },
            }
        );
        labelElements.push( iconElement );
    }

    labelElements.push( gateway.title );

    const label = createElement(
        'span',
        { style: { display: 'flex', alignItems: 'center' } },
        ...labelElements
    );

    return {
        name: gateway.id,
        label: label,
        paymentMethodId: gateway.id,
        edit: createElement( 'div', null, '' ),
        canMakePayment: () => {
            if ( gateway && gateway.is_admin ) {
                return true;
            }
            if ( gateway && gateway.gateway_code === 'APPLEPAY' ) {
                return check_apple_pay_availability();
            }
            return true;
        },
        ariaLabel: gateway.title,
        content: createElement( Content, null ),
    };
};

const registerMultiSafepayPaymentMethods = ( multisafepay_gateways ) => {
    /**
     * Registers all gateways and triggers an update of available methods in the payment store.
     *
     * @param {Array} args.multisafepay_gateways
     * @returns {void}
     */
    multisafepay_gateways.forEach(
        ( gateway ) => {
            if ( gateway.is_admin || ( gateway.id !== 'multisafepay_applepay' ) || check_apple_pay_availability() ) {
                try {
                    registerPaymentMethod( createOptions( gateway ) );
                } catch ( e ) {
                    multisafepayBlocksLogErrorOnce( ! ! ( gateway && gateway.payment_component_config && gateway.payment_component_config.debug ), 'Blocks: Failed to register payment method.', e );
                }
            }
        }
    );

    const refreshPaymentMethods = () => {
        try {
            const paymentStore = wpDispatch( 'wc/store/payment' );
            if ( paymentStore && paymentStore.__internalUpdateAvailablePaymentMethods ) {
                paymentStore.__internalUpdateAvailablePaymentMethods();
                return true;
            }
        } catch ( e ) {
            return false;
        }

        return false;
    };

    if ( ! refreshPaymentMethods() ) {
        multisafepayBlocksRetryUntil( refreshPaymentMethods, { intervalMs: 200, maxAttempts: 50, immediate: false } );
    }

    // Wallet-direct buttons are rendered in the checkout actions (Place order) area.
    // Do not disable the Place order button: the wallet button itself triggers submission.
};

const registerWhenReady = () => {
    /**
     * Attempts to register payment methods. Returns false if registries aren't ready yet.
     *
     * @returns {boolean}
     */
    if ( ! window || typeof registerPaymentMethod !== 'function' || ! window.multisafepay_gateways ) {
        return false;
    }

    registerMultiSafepayPaymentMethods( window.multisafepay_gateways );
    return true;
};

/**
 * Bootstraps MultiSafepay Block payment methods registration.
 *
 * Safe to call multiple times; internally it retries until the required globals exist.
 *
 * @returns {void}
 */
export function bootstrapMultisafepayBlocksPaymentMethods() {
    if ( ! registerWhenReady() ) {
        multisafepayBlocksRetryUntil( registerWhenReady, { intervalMs: 200, maxAttempts: 50, immediate: false } );
    }
}
