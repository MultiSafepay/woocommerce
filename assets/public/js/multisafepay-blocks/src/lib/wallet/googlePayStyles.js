import {
    BLOCKS_WALLET_BUTTON_BORDER_RADIUS_PX,
    BLOCKS_WALLET_BUTTON_HEIGHT_PX,
    BLOCKS_WALLET_BUTTON_MAX_WIDTH_PX,
} from '../constants';
import { check_apple_pay_availability, getPreferredPlaceOrderButtonHeightPx } from '../dom/checkout';

/**
 * @file Google Pay button styling helpers for Checkout Blocks.
 *
 * Google Pay injects its own DOM and can apply styles asynchronously.
 * These helpers normalize sizing/centering to avoid layout jumps and Safari quirks.
 */

export function getPreferredWalletButtonHeightPx() {
    /**
     * Returns the target wallet button height in Blocks.
     *
     * @returns {number}
     */
    if ( typeof BLOCKS_WALLET_BUTTON_HEIGHT_PX === 'number' && BLOCKS_WALLET_BUTTON_HEIGHT_PX > 0 ) {
        return Math.round( BLOCKS_WALLET_BUTTON_HEIGHT_PX );
    }

    return getPreferredPlaceOrderButtonHeightPx();
}

export function getPreferredWalletButtonMaxWidthPx() {
    /**
     * Returns the max width for wallet buttons in Blocks.
     *
     * @returns {number}
     */
    if ( typeof BLOCKS_WALLET_BUTTON_MAX_WIDTH_PX === 'number' && BLOCKS_WALLET_BUTTON_MAX_WIDTH_PX > 0 ) {
        return Math.round( BLOCKS_WALLET_BUTTON_MAX_WIDTH_PX );
    }

    return 0;
}

export function getPreferredWalletButtonBorderRadiusPx() {
    /**
     * Returns the border radius for wallet buttons in Blocks.
     *
     * @returns {number}
     */
    if ( typeof BLOCKS_WALLET_BUTTON_BORDER_RADIUS_PX === 'number' && BLOCKS_WALLET_BUTTON_BORDER_RADIUS_PX >= 0 ) {
        return Math.round( BLOCKS_WALLET_BUTTON_BORDER_RADIUS_PX );
    }

    return 0;
}

export function multisafepayBlocksEnsureGooglePayCssOverrides() {
    /**
     * Injects a style tag with overrides to keep GPay logo sizing stable in Blocks.
     *
     * Safe to call multiple times.
     *
     * @returns {void}
     */
    try {
        const styleId = 'msp-blocks-gpay-css-overrides';
        if ( document.getElementById( styleId ) ) {
            return;
        }
        const style = document.createElement( 'style' );
        style.id    = styleId;

        // The plugin ships a legacy CSS that targets `.gpay-button.plain/.short` globally.
        // In Blocks (notably Safari) this can distort the logo. Add a more specific
        // override scoped to the Blocks container.
        // The GPay logo SVG used in the background-image is 41x17. Locking the
        // background-size to fixed pixels avoids a subtle initial size jump while
        // the SVG loads or Google adjusts styles after insertion.
        style.textContent = [
            '.multisafepay-googlepay-direct .gpay-button,',
            '.multisafepay-googlepay-direct .gpay-button.short,',
            '.multisafepay-googlepay-direct .gpay-button.plain,',
            '.multisafepay-googlepay-direct .gpay-button.black.short,',
            '.multisafepay-googlepay-direct .gpay-button.black.plain {',
            '  background-size: 41px 17px !important;',
            '  background-position: 50% 50% !important;',
            '  background-position-x: 50% !important;',
            '  background-position-y: 50% !important;',
            '  background-repeat: no-repeat !important;',
            '  transition: none !important;',
            '  width: 100% !important;',
            '  max-width: 100% !important;',
            '  min-width: 0 !important;',
            '  min-height: 0 !important;',
            '  border-radius: inherit !important;',
            '}',
        ].join( '\n' );

        ( document.head || document.documentElement ).appendChild( style );
    } catch ( e ) {
        // no-op
    }
}

// Inject the override stylesheet as early as possible, so the first paint of
// Google Pay's button uses the final logo sizing and centering.
try {
    multisafepayBlocksEnsureGooglePayCssOverrides();
} catch ( e ) {
    // no-op
}

export function multisafepayBlocksNormalizeGooglePayButtonStyles( containerElOrButtonEl, borderRadiusPx ) {
    /**
     * Normalizes Google Pay button inline styles (size, background positioning, radius).
     *
     * @param {HTMLElement} containerElOrButtonEl
     * @param {number} borderRadiusPx
     * @returns {void}
     */
    try {
        const preferredHeightPx   = getPreferredWalletButtonHeightPx();
        const preferredMaxWidthPx = getPreferredWalletButtonMaxWidthPx();

        const applyToEl = ( el ) => {
            if ( ! el || ! el.style || typeof el.style.setProperty !== 'function' ) {
                return;
            }

            // The plugin's public CSS includes global rules for `.gpay-button.plain/.short`
            // intended for legacy checkout. In Blocks this can distort the logo.
            // Also, enforce a stable initial background-size to avoid a brief "zoom" effect
            // when Google applies styles asynchronously after insertion.
            const isGpayButton = ! ! ( el.classList && el.classList.contains( 'gpay-button' ) );
            el.style.setProperty( 'background-size', isGpayButton ? '41px 17px' : 'contain', 'important' );
            el.style.setProperty( 'background-position', '50% 50%', 'important' );
            el.style.setProperty( 'background-position-x', '50%', 'important' );
            el.style.setProperty( 'background-position-y', '50%', 'important' );
            el.style.setProperty( 'background-repeat', 'no-repeat', 'important' );
            el.style.setProperty( 'transition', 'none', 'important' );
            el.style.setProperty( 'width', '100%', 'important' );
            if ( typeof preferredMaxWidthPx === 'number' && preferredMaxWidthPx > 0 ) {
                el.style.setProperty( 'max-width', preferredMaxWidthPx + 'px', 'important' );
            }
            el.style.setProperty( 'min-width', '0px', 'important' );
            if ( typeof preferredHeightPx === 'number' && preferredHeightPx > 0 ) {
                el.style.setProperty( 'height', preferredHeightPx + 'px', 'important' );
                el.style.setProperty( 'min-height', preferredHeightPx + 'px', 'important' );
            }
            el.style.setProperty( 'border-radius', 'inherit', 'important' );

            if ( typeof borderRadiusPx === 'number' && borderRadiusPx > 0 ) {
                el.style.setProperty( 'border-radius', borderRadiusPx + 'px', 'important' );
                el.style.setProperty( 'overflow', 'hidden', 'important' );
            }
        };

        // Apply to the element itself.
        applyToEl( containerElOrButtonEl );

        // Also apply to any `.gpay-button` descendants (covers cases where Google wraps).
        if ( containerElOrButtonEl && typeof containerElOrButtonEl.querySelectorAll === 'function' ) {
            const descendants = containerElOrButtonEl.querySelectorAll( '.gpay-button, .gpay-button.plain, .gpay-button.short' );
            descendants.forEach( ( el ) => applyToEl( el ) );
        }
    } catch ( e ) {
        // no-op
    }
}

export function multisafepayBlocksDebugGooglePayButtonComputed( logDebug, containerEl ) {
    /**
     * Debug helper that logs computed style metrics for the rendered GPay button.
     *
     * @param {Function} logDebug
     * @param {HTMLElement} containerEl
     * @returns {void}
     */
    try {
        if ( ! containerEl || typeof containerEl.querySelector !== 'function' ) {
            return;
        }
        const btn = containerEl.querySelector( '.gpay-button' );
        if ( ! btn ) {
            logDebug( '[GooglePay] button computed', { found: false } );
            return;
        }
        const cs = window.getComputedStyle ? window.getComputedStyle( btn ) : null;
        logDebug(
            '[GooglePay] button computed',
            {
                found: true,
                tagName: String( btn.tagName || '' ),
                className: String( btn.className || '' ),
                width: cs && cs.width ? String( cs.width ) : '',
                height: cs && cs.height ? String( cs.height ) : '',
                backgroundSize: cs && cs.backgroundSize ? String( cs.backgroundSize ) : '',
                backgroundImagePresent: ! ! ( cs && cs.backgroundImage && cs.backgroundImage !== 'none' ),
            }
        );
    } catch ( e ) {
        // no-op
    }
}

export function isApplePayCompatibleForGooglePayStylingFallback() {
    /**
     * Some themes align wallet buttons consistently only if Apple Pay is available.
     *
     * @returns {boolean}
     */
    return check_apple_pay_availability();
}
