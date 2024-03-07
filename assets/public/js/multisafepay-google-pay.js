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

(function($) {
    'use strict';
    $(
        function() {
            check_if_google_pay_is_available();
            $( document ).ajaxComplete(
                function( e, xhr, settings ) {
                    if ( settings.url.indexOf( '?wc-ajax=update_order_review' ) !== -1 ) {
                        check_if_google_pay_is_available();
                    }
                }
            );
        }
    );

    /**
     * Check if Google Pay is available
     */
    function check_if_google_pay_is_available() {
        if ( ! window.google || ! window.google.payments || ! window.google.payments.api ) {
            console.error( 'Error initializing Google Pay: Script not loaded' );
        } else {
            let googlePayClient = new google.payments.api.PaymentsClient( { environment: 'PRODUCTION' } );

            const baseCardPaymentMethod = {
                type: 'CARD',
                parameters: {
                    allowedCardNetworks: ['VISA', 'MASTERCARD'],
                    allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS']
                }
            };

            const googlePayBaseConfiguration = {
                apiVersion: 2,
                apiVersionMinor: 0,
                allowedPaymentMethods: [baseCardPaymentMethod],
                existingPaymentMethodRequired: true
            }
            googlePayClient.isReadyToPay( googlePayBaseConfiguration ).then(
                ( response ) => {
                    if ( ! response.result ) {
                        $( '.payment_method_multisafepay_googlepay' ).remove();
                    }
                }
            );
        }
    }
})( jQuery );
