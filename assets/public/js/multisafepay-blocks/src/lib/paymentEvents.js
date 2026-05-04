/**
 * Registers a checkout payment lifecycle callback with backwards compatibility.
 *
 * Newer Woo Blocks expose `onPaymentSetup`; older versions expose
 * `onPaymentProcessing`. We prefer setup to avoid deprecation warnings while
 * keeping compatibility with older stores.
 *
 * @package MultiSafepay
 * @param {Object} eventRegistration Blocks event registration object.
 * @param {Function} callback Callback to execute during payment submit lifecycle.
 * @returns {Function|undefined} Unsubscribe callback when provided by Blocks.
 */

export const multisafepayBlocksRegisterOnPaymentSubmit = ( eventRegistration, callback ) => {
    if ( ! eventRegistration || typeof callback !== 'function' ) {
        return;
    }

    if ( typeof eventRegistration.onPaymentSetup === 'function' ) {
        return eventRegistration.onPaymentSetup( callback );
    }

    if ( typeof eventRegistration.onPaymentProcessing === 'function' ) {
        return eventRegistration.onPaymentProcessing( callback );
    }
};
