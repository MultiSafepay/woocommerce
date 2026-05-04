/**
 * Shared constants for Checkout Blocks integration.
 *
 * @package MultiSafepay
 */

/**
 * Max width for wallet direct buttons in Blocks checkout.
 *
 * Use `max-width` so the button stays responsive on mobile but does not become awkwardly
 * wide on desktop.
 *
 * @type {number}
 */
export const BLOCKS_WALLET_BUTTON_MAX_WIDTH_PX = 240;

/**
 * Target height for wallet direct buttons in Blocks checkout.
 *
 * If this is set to `0` or `null` (not recommended for a constant), the implementation
 * falls back to the Place order button height (theme-controlled).
 *
 * @type {number}
 */
export const BLOCKS_WALLET_BUTTON_HEIGHT_PX = 40;

/**
 * Border radius in px for wallet direct buttons (Apple Pay / Google Pay) in Blocks.
 *
 * Set to `0` to keep square corners (matches legacy CSS defaults).
 *
 * @type {number}
 */
export const BLOCKS_WALLET_BUTTON_BORDER_RADIUS_PX = 3;
