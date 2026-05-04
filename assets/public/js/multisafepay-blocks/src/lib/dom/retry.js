/**
 * Repeatedly calls a function until it returns a truthy value, or we hit the attempt limit.
 *
 * Used in Blocks to wait for WooCommerce registries/stores/DOM to become available without
 * hard dependencies on load order.
 *
 * @package MultiSafepay
 * @param {Function} fn Function to run. If it returns truthy, retrying stops.
 * @param {Object} [options]
 * @param {number} [options.intervalMs=200] Interval between attempts.
 * @param {number} [options.maxAttempts=50] Maximum number of attempts (best-effort).
 * @param {boolean} [options.immediate=true] If true, runs once immediately before starting the interval.
 * @returns {Function} Stop function that cancels further retries.
 */

export function multisafepayBlocksRetryUntil(
    fn,
    {
        intervalMs = 200,
        maxAttempts = 50,
        immediate = true,
    } = {}
) {
    if ( typeof fn !== 'function' ) {
        return () => {};
    }

    let intervalId = null;
    let attempts   = 0;

    const clear = () => {
        try {
            if ( intervalId ) {
                clearInterval( intervalId );
            }
        } catch ( e ) {
            // no-op
        }
        intervalId = null;
    };

    const tryOnce = () => {
        try {
            return ! ! fn();
        } catch ( e ) {
            return false;
        }
    };

    if ( immediate ) {
        if ( tryOnce() ) {
            return () => {};
        }
    }

    intervalId        = setInterval(
        () => {
            attempts += 1;
            // Keep original semantics: still try `fn()` on the last attempt,
            // then stop when attempts to exceed maxAttempts.
            const done = tryOnce();
            if ( done || attempts > maxAttempts ) {
                clear();
            }
        },
        intervalMs
    );

    return clear;
}
