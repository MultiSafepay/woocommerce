import { dispatch as wpDispatch, select as wpSelect } from '@wordpress/data';

/**
 * @file DOM + store helpers for WooCommerce Checkout Blocks.
 *
 * Keep these helpers defensive: Blocks markup and store APIs can change between versions.
 *
 * @package MultiSafepay
 */

/**
 * Checks whether Apple Pay can make payments in the current browser.
 *
 * @returns {boolean}
 */
export function check_apple_pay_availability() {
    try {
        return ! ! ( window.ApplePaySession && typeof ApplePaySession.canMakePayments === 'function' && ApplePaySession.canMakePayments() );
    } catch ( e ) {
        return false;
    }
}

/**
 * Triggers the Blocks "Place order" button click (best-effort).
 *
 * Used by wallet direct buttons after injecting payment meta into the store.
 *
 * @returns {boolean} True if a click was triggered.
 */
export function triggerBlocksPlaceOrderClick() {
    // Best-effort DOM trigger; avoids depending on internal WC Blocks store APIs.
    const candidates = [
        'button.wc-block-components-checkout-place-order-button',
        '.wc-block-checkout__actions button.wc-block-components-checkout-place-order-button',
        'form.wc-block-checkout__form button.wc-block-components-checkout-place-order-button',
    ];

    for ( const selector of candidates ) {
        const button = document.querySelector( selector );
        if ( ! button || typeof button.click !== 'function' ) {
            continue;
        }

        if ( ! button.disabled ) {
            button.click();
            return true;
        }
    }

    return false;
}

/**
 * Finds the Checkout Blocks "Place order" button element.
 *
 * @returns {HTMLButtonElement|null}
 */
export function getBlocksPlaceOrderButton() {
    const candidates = [
        'button.wc-block-components-checkout-place-order-button',
        '.wc-block-checkout__actions button.wc-block-components-checkout-place-order-button',
        'form.wc-block-checkout__form button.wc-block-components-checkout-place-order-button',
    ];

    for ( const selector of candidates ) {
        const button = document.querySelector( selector );
        if ( button ) {
            return button;
        }
    }

    return null;
}

/**
 * Returns the Blocks checkout actions container (the section that contains the Place order button).
 *
 * @returns {HTMLElement|null}
 */
export function getBlocksCheckoutActionsContainer() {
    const candidates = [
        '.wc-block-checkout__actions',
        'form.wc-block-checkout__form .wc-block-checkout__actions',
    ];

    for ( const selector of candidates ) {
        const el = document.querySelector( selector );
        if ( el ) {
            return el;
        }
    }

    return null;
}

/**
 * Attempts to find the main checkout content container.
 *
 * This is used as a constraint element for positioning wallet buttons.
 *
 * @param {HTMLElement|null} [fromEl=null]
 * @returns {HTMLElement|null}
 */
export function getBlocksCheckoutMainContainer( fromEl = null ) {
    try {
        const base = fromEl || getBlocksCheckoutActionsContainer();
        if ( base && typeof base.closest === 'function' ) {
            // In Blocks, checkout commonly uses a Sidebar Layout container.
            // The left content column is usually `__content`.
            const sidebarLayout = base.closest( '.wc-block-components-sidebar-layout' );
            if ( sidebarLayout && typeof sidebarLayout.querySelector === 'function' ) {
                const content = sidebarLayout.querySelector( '.wc-block-components-sidebar-layout__content' ) ||
                    sidebarLayout.querySelector( '.wc-block-components-sidebar-layout__main' ) ||
                    sidebarLayout.querySelector( '.wc-block-components-sidebar-layout__body' );
                if ( content ) {
                    return content;
                }
            }

            const closestMain = base.closest( '.wc-block-checkout__main' );
            if ( closestMain ) {
                return closestMain;
            }
        }

        const byQuery = document.querySelector( '.wc-block-components-sidebar-layout__content' ) ||
            document.querySelector( '.wc-block-components-sidebar-layout__main' ) ||
            document.querySelector( '.wc-block-checkout__main' );
        if ( byQuery ) {
            return byQuery;
        }
    } catch ( e ) {
        // no-op
    }

    return null;
}

/**
 * Returns the layout container near "Place order".
 *
 * @returns {HTMLElement|null}
 */
export function getBlocksPlaceOrderLayoutContainer() {
    const placeOrder = getBlocksPlaceOrderButton();
    if ( placeOrder && placeOrder.parentElement ) {
        return placeOrder.parentElement;
    }

    return getBlocksCheckoutActionsContainer();
}

/**
 * Reads the currently active payment method ID from the Blocks payment store.
 *
 * @returns {string}
 */
export function getBlocksActivePaymentMethodId() {
    try {
        const store = wpSelect( 'wc/store/payment' );
        if ( ! store ) {
            return '';
        }

        const tryFns = [ 'getActivePaymentMethod', 'getActivePaymentMethodName', 'getActivePaymentMethodId' ];
        for ( const fn of tryFns ) {
            if ( typeof store[ fn ] === 'function' ) {
                const res = store[ fn ]();
                if ( typeof res === 'string' ) {
                    return res;
                }
                if ( res && typeof res === 'object' ) {
                    if ( typeof res.name === 'string' ) {
                        return res.name;
                    }
                    if ( typeof res.id === 'string' ) {
                        return res.id;
                    }
                }
            }
        }
    } catch ( e ) {
        // no-op
    }

    return '';
}

/**
 * Sets the active payment method in the Blocks payment store.
 *
 * @param {string} methodId
 * @returns {boolean}
 */
export function setBlocksActivePaymentMethodId( methodId ) {
    if ( ! methodId || typeof methodId !== 'string' ) {
        return false;
    }

    try {
        const store = wpDispatch( 'wc/store/payment' );
        if ( ! store ) {
            return false;
        }

        const tryFns = [
            '__internalSetActivePaymentMethod',
            'setActivePaymentMethod',
            'setActivePaymentMethodName',
            'setActivePaymentMethodId',
        ];

        for ( const fn of tryFns ) {
            if ( typeof store[ fn ] === 'function' ) {
                store[ fn ]( methodId );
                return true;
            }
        }
    } catch ( e ) {
        // no-op
    }

    return false;
}

/**
 * Sets payment-method data (meta) in the Blocks payment store.
 *
 * This is how wallet direct flows pass tokens into the checkout request.
 *
 * @param {Object} paymentMethodData
 * @returns {boolean}
 */
export function setBlocksPaymentMethodData( paymentMethodData ) {
    if ( ! paymentMethodData || typeof paymentMethodData !== 'object' ) {
        return false;
    }

    try {
        const store = wpDispatch( 'wc/store/payment' );
        if ( ! store ) {
            return false;
        }

        const tryFns = [ '__internalSetPaymentMethodData', 'setPaymentMethodData' ];
        for ( const fn of tryFns ) {
            if ( typeof store[ fn ] === 'function' ) {
                store[ fn ]( paymentMethodData );
                return true;
            }
        }
    } catch ( e ) {
        // no-op
    }

    return false;
}

/**
 * Hides or restores the Place order button while wallet direct buttons are present.
 *
 * @param {boolean} hidden
 * @returns {boolean}
 */
export function setBlocksPlaceOrderHiddenByWallet( hidden ) {
    const button = getBlocksPlaceOrderButton();
    if ( ! button ) {
        return false;
    }

    try {
        if ( hidden ) {
            if ( button.dataset && button.dataset.mspWalletHidden !== '1' ) {
                button.dataset.mspWalletHidden          = '1';
                button.dataset.mspWalletOriginalDisplay = typeof button.style.display === 'string' ? button.style.display : '';
            }
            button.style.display = 'none';
        } else {
            if ( button.dataset && button.dataset.mspWalletHidden === '1' ) {
                const original       = button.dataset.mspWalletOriginalDisplay;
                button.style.display = typeof original === 'string' ? original : '';
                delete button.dataset.mspWalletHidden;
                delete button.dataset.mspWalletOriginalDisplay;
            }
        }
    } catch ( e ) {
        return false;
    }

    return true;
}

/**
 * Disables or restores the Place order button when a gateway is configured as QR-only.
 *
 * Preserves any pre-existing disabled state (e.g., validation) via dataset bookkeeping.
 *
 * @param {boolean} disabled
 * @returns {boolean}
 */
export function setBlocksPlaceOrderDisabledByQrOnly( disabled ) {
    const button = getBlocksPlaceOrderButton();
    if ( ! button ) {
        return false;
    }

    try {
        if ( disabled ) {
            if ( button.dataset && button.dataset.mspQrOnlyDisabled !== '1' ) {
                button.dataset.mspQrOnlyDisabled         = '1';
                button.dataset.mspQrOnlyOriginalDisabled = button.disabled ? '1' : '0';
            }

            button.disabled = true;
            try {
                button.setAttribute( 'aria-disabled', 'true' );
            } catch ( e2 ) {
                // no-op
            }
        } else {
            if ( button.dataset && button.dataset.mspQrOnlyDisabled === '1' ) {
                const original         = button.dataset.mspQrOnlyOriginalDisabled;
                const shouldBeDisabled = original === '1';
                button.disabled        = shouldBeDisabled;
                try {
                    if ( shouldBeDisabled ) {
                        button.setAttribute( 'aria-disabled', 'true' );
                    } else {
                        button.removeAttribute( 'aria-disabled' );
                    }
                } catch ( e2 ) {
                    // no-op
                }
                delete button.dataset.mspQrOnlyDisabled;
                delete button.dataset.mspQrOnlyOriginalDisabled;
            }
        }
    } catch ( e ) {
        return false;
    }

    return true;
}

/**
 * Disables or restores the Place order button while a payment component is loading.
 *
 * Preserves any pre-existing disabled state via dataset bookkeeping.
 *
 * @param {boolean} disabled
 * @returns {boolean}
 */
export function setBlocksPlaceOrderDisabledByLoading( disabled ) {
    const button = getBlocksPlaceOrderButton();
    if ( ! button ) {
        return false;
    }

    try {
        if ( disabled ) {
            if ( button.dataset && button.dataset.mspLoadingDisabled !== '1' ) {
                button.dataset.mspLoadingDisabled         = '1';
                button.dataset.mspLoadingOriginalDisabled = button.disabled ? '1' : '0';
            }

            button.disabled = true;
            try {
                button.setAttribute( 'aria-disabled', 'true' );
                button.setAttribute( 'aria-busy', 'true' );
            } catch ( e2 ) {
                // no-op
            }
        } else {
            if ( button.dataset && button.dataset.mspLoadingDisabled === '1' ) {
                const original         = button.dataset.mspLoadingOriginalDisabled;
                const shouldBeDisabled = original === '1';
                button.disabled        = shouldBeDisabled;
                try {
                    if ( shouldBeDisabled ) {
                        button.setAttribute( 'aria-disabled', 'true' );
                    } else {
                        button.removeAttribute( 'aria-disabled' );
                    }
                    button.removeAttribute( 'aria-busy' );
                } catch ( e2 ) {
                    // no-op
                }
                delete button.dataset.mspLoadingDisabled;
                delete button.dataset.mspLoadingOriginalDisabled;
            }
        }
    } catch ( e ) {
        return false;
    }

    return true;
}

/**
 * Picks a reasonable height for wallet buttons, matching the Place order button when possible.
 *
 * @returns {number}
 */
export function getPreferredPlaceOrderButtonHeightPx() {
    const fallback = check_apple_pay_availability() ? 65 : 64;

    try {
        const btn = document.querySelector( 'button.wc-block-components-checkout-place-order-button' ) ||
            document.querySelector( '.wc-block-checkout__actions button[type="submit"]' ) ||
            document.querySelector( 'form.wc-block-checkout__form button[type="submit"]' );

        if ( btn ) {
            const computed = window.getComputedStyle( btn );
            const h        = computed && computed.height ? parseFloat( computed.height ) : NaN;
            if ( ! Number.isNaN( h ) && h > 0 ) {
                return Math.round( h );
            }
        }
    } catch ( e ) {
        // no-op
    }

    return fallback;
}
