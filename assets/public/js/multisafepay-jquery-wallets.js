/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 * @package     MultiSafepay
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

(function ($) {
    $(
        function (
        ) {
            /**
             * Create an instance of the FieldsValidator class
             *
             * @type {FieldsValidator}
             */
            const validatorInstance = new FieldsValidator();

            /**
             * Listen to the Select2 events to remove
             * the inline styles and error messages
             *
             * @returns {void}
             */
            function select2Validation() {
                $( 'select' ).each(
                    function() {
                        $( this ).on(
                            'select2:select',
                            function() {
                                debugDirect( 'Select2 action initialized for field: ' + this.name, debugStatus, 'log' );
                                const select2Container = $( this ).closest( '.validate-required' ).find( '.select2-selection' );
                                if ( select2Container.length ) {
                                    select2Container.attr( 'style', '' );
                                }
                                validatorInstance.removeErrorMessage( this.name );
                            }
                        );
                    }
                );
            }

            /**
             * Get the total price from the server side
             *
             * @returns {Promise<number>}
             */
            async function getTotalPriceFromServer() {
                if (
                    ( typeof configAdminUrlAjax !== 'undefined' ) &&
                    ( typeof configAdminUrlAjax.location !== 'undefined' ) &&
                    ( typeof configAdminUrlAjax.nonce !== 'undefined')
                ) {
                    try {
                        const response = await $.ajax(
                            {
                                url: configAdminUrlAjax.location,
                                type: 'POST',
                                data: {
                                    'nonce': configAdminUrlAjax.nonce,
                                    'action': 'get_updated_total_price',
                                }
                            }
                        );

                        // Check if totalPrice is a valid number
                        if ( ( typeof response.totalPrice === 'number' ) && ! isNaN( response.totalPrice ) ) {
                            // Round the number to avoid floating-point precision issues
                            return Math.round( response.totalPrice ) / 100;
                        } else {
                            // Handle invalid totalPrice
                            debugDirect( 'Invalid or non-numeric total price received: ' + JSON.stringify( response.totalPrice ), debugStatus );
                            return 0;
                        }
                    } catch ( error ) {
                        console.error( 'Error trying to get the total price from the server side', error );
                        return 0;
                    }
                } else {
                    debugDirect( 'Values for configAdminUrlAjax not defined', debugStatus );
                    return 0;
                }
            }

            /**
             * Check if the total price has been changed in the checkout page
             *
             * @returns {void}
             */
            async function checkActualTotalPrice() {
                try {
                    const totalNumber = await getTotalPriceFromServer();
                    if ( totalNumber > 0 ) {
                        // Check if configGooglePay and configApplePay are defined
                        // before attempting to access their properties
                        if (
                            ( typeof configGooglePay !== 'undefined' ) &&
                            ( typeof configGooglePay.totalPrice !== 'undefined' ) &&
                            ( totalNumber !== configGooglePay.totalPrice )
                        ) {
                            configGooglePay.totalPrice = totalNumber;
                        }

                        if (
                            ( typeof configApplePay !== 'undefined' ) &&
                            ( typeof configApplePay.totalPrice !== 'undefined' ) &&
                            ( totalNumber !== configApplePay.totalPrice )
                        ) {
                            configApplePay.totalPrice = totalNumber;
                        }
                        debugDirect( 'Total price is ' + totalNumber.toFixed( 2 ), debugStatus, 'log' );
                    } else {
                        debugDirect( 'Total price was not provided by the server', debugStatus, 'warn' );
                    }
                } catch ( error ) {
                    console.error( 'Error fetching the total price from getTotalPriceFromServer()', error );
                } finally {
                    $( '.gpay-button' ).prop( 'disabled', false );
                    $( '.apple-pay-button' ).prop( 'disabled', false );
                }
            }

            /**
             * Initialize the class to launch Google Pay and Apple Pay
             * as direct payment methods
             */
            new GoogleApplePayDirectHandler();

            /**
             * Initialize the listening to the Select2 events
             */
            select2Validation();

            /**
             * Listen to the ajaxComplete event to check if the total price
             * has been changed in the checkout page
             * and enable the Google Pay and Apple Pay buttons
             */
            $( document ).ajaxComplete(
                function( e, xhr, settings ) {
                    if ( settings.url.indexOf( '?wc-ajax=update_order_review' ) !== -1 ) {
                        $( document ).one(
                            'update_checkout',
                            () => {
                                $( '.gpay-button' ).prop( 'disabled', true );
                                $( '.apple-pay-button' ).prop( 'disabled', true );
                            }
                        );
                        $( document ).one(
                            'updated_checkout',
                            () => {
                                checkActualTotalPrice();
                                // Remove the orphan error messages from the notice group
                                validatorInstance.removeOrphanErrorMessages();
                            }
                        );
                        $( document ).on(
                            'payment_method_selected',
                            () => {
                                $( '.gpay-button' ).prop( 'disabled', false );
                                $( '.apple-pay-button' ).prop( 'disabled', false );
                            }
                        );
                        new GoogleApplePayDirectHandler();
                    }
                }
            );
        }
    );
})( jQuery );
