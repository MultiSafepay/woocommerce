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

(function (multisafepay_payment_component_gateways, $) {

    const PAYMENT_METHOD_SELECTOR = 'ul.wc_payment_methods input[type=\'radio\'][name=\'payment_method\']';
    const FORM_BUTTON_SELECTOR    = '#place_order';

    class MultiSafepayPaymentComponent {

        payment_component                    = false;
        config                               = [];
        gateway                              = '';
        payment_component_container_selector = '';

        constructor(config, gateway) {
            this.payment_component_container_selector = '#' + gateway + '_payment_component_container';
            this.payment_component                    = false;
            this.config                               = config;
            this.gateway                              = gateway;

            // Triggered when change the payment method selected
            $( document ).on( 'payment_method_selected', ( event ) => { this.on_payment_method_selected( event ); } );

            // Triggered when something changes in the checkout and start the process to refresh everything
            $( document ).on( 'update_checkout', ( event ) => { this.on_update_checkout( event ); } );

            // Triggered when something changed in the checkout and the process to refresh everything is finished
            $( document ).on( 'updated_checkout', ( event ) => { this.on_updated_checkout( event ); } );

            // Trigered when the checkout loads
            $( document ).on( 'init_checkout', ( event ) => { this.on_init_checkout( event ); } );

            // Trigered when user click on submit button of the checkout form
            $( document ).on( 'click', FORM_BUTTON_SELECTOR, ( event ) => { this.on_click_place_order( event ); } );

        }

        on_payment_method_selected( event ) {
            this.logger( event.type );

            if ( false === this.is_selected() || false === this.is_payment_component_gateway() ) {
                return;
            }
            this.maybe_init_payment_component();
        }

        on_update_checkout( event ) {
            this.logger( event.type );

            if ( false === this.is_selected() || false === this.is_payment_component_gateway() ) {
                return;
            }

            this.maybe_init_payment_component();
        }

        on_updated_checkout( event ) {
            this.logger( event.type );

            if ( false === this.is_selected() || false === this.is_payment_component_gateway() ) {
                return;
            }

            this.refresh_payment_component_config();
        }

        on_init_checkout( event ) {
            this.logger( event.type );

            if ( false === this.is_selected() || false === this.is_payment_component_gateway() ) {
                return;
            }

            this.maybe_init_payment_component();
        }

        refresh_payment_component_config() {
            $.ajax(
                {
                    url: this.config.ajax_url,
                    type: 'POST',
                    data: {
                        'nonce': this.config.nonce,
                        'action': 'get_payment_component_arguments',
                        'gateway_id': this.gateway,
                        'gateway': this.config.gateway,
                    },
                    beforeSend: function() {
                        $( this.payment_component_container_selector ).html( '' );
                        this.show_loader();
                    }.bind( this ),
                    complete: function () {
                        this.payment_component = null;
                        this.reinit_payment_component();
                        this.hide_loader();
                    }.bind( this ),
                    success: function ( response ) {
                        this.config.orderData = response.orderData;
                    }.bind( this )
                }
            );
        }

        on_click_place_order( event ) {
            this.logger( event.type );
            this.remove_errors();

            if ( true === this.is_selected() && true === this.is_payment_component_gateway() ) {
                if (this.get_payment_component().hasErrors()) {
                    this.logger( this.get_payment_component().getErrors() );
                    this.insert_errors( this.get_payment_component().getErrors() );
                } else {
                    this.remove_payload_and_tokenize();
                    this.logger( this.get_payment_component().getOrderData() );
                    var payload  = this.get_payment_component().getPaymentData().payload;
                    var tokenize = this.get_payment_component().getPaymentData().tokenize ? this.get_payment_component().getPaymentData().tokenize : '0';
                    this.insert_payload_and_tokenize( payload, tokenize );
                }
                $( '.woocommerce-checkout' ).submit();
            }

        }

        is_selected() {
            if ( $( PAYMENT_METHOD_SELECTOR + ":checked" ).val() === this.gateway ) {
                return true;
            }
            return false;
        }

        is_payment_component_gateway() {
            if ( $.inArray( $( PAYMENT_METHOD_SELECTOR + ":checked" ).val(), multisafepay_payment_component_gateways ) !== -1 ) {
                return true;
            }
            return false;
        }

        get_new_payment_component() {
            return new MultiSafepay(
                {
                    env: this.config.env,
                    apiToken: this.config.api_token,
                    order: this.config.orderData,
                    recurring: this.config.recurring,
                }
            );
        }

        get_payment_component() {
            if ( ! this.payment_component ) {
                this.payment_component = this.get_new_payment_component();
            }
            return this.payment_component;
        }

        init_payment_component() {
            this.show_loader();
            const multisafepay_component = this.get_payment_component();
            multisafepay_component.init(
                'payment',
                {
                    container: this.payment_component_container_selector,
                    gateway: this.config.gateway,
                    onLoad: state => { this.logger( 'onLoad' ); },
                    onError: state => { this.logger( 'onError' ); }
                }
            );
            this.hide_loader();
        }

        reinit_payment_component() {
            this.init_payment_component();
        }

        maybe_init_payment_component() {
            // there is no way to know if the payment component exist or not; except for checking the DOM elements
            if ( $( this.payment_component_container_selector + ' > .msp-container-ui' ).length > 0) {
                return;
            }
            this.logger( 'Container exist' );
            this.init_payment_component();
        }

        show_loader() {
            $( this.payment_component_container_selector ).html( '<div class="loader-wrapper"><span class="loader"></span></span></div>' );
            $( FORM_BUTTON_SELECTOR ).prop( 'disabled', true );
        }

        hide_loader() {
            $( this.payment_component_container_selector + ' .loader-wrapper' ).remove();
            $( FORM_BUTTON_SELECTOR ).prop( 'disabled', false );
        }

        insert_payload_and_tokenize( payload, tokenize ) {
            $( '#' + this.gateway + '_payment_component_payload' ).val( payload );
            $( '#' + this.gateway + '_payment_component_tokenize' ).val( tokenize );
        }

        remove_payload_and_tokenize() {
            $( '#' + this.gateway + '_payment_component_payload' ).val( '' );
            $( '#' + this.gateway + '_payment_component_tokenize' ).val( '' );
        }

        insert_errors( errors ) {
            const gateway_id = this.gateway;
            $.each(
                errors.errors,
                function( index, value ) {
                    $( 'form.woocommerce-checkout' ).append(
                        '<input type="hidden" class="' + gateway_id + '_payment_component_errors" name="' + gateway_id + '_payment_component_errors[]" value="' + value.message + '" />'
                    );
                }
            );
        }

        remove_errors() {
            $( 'form.woocommerce-checkout .' + this.gateway + '_payment_component_errors' ).remove();
        }

        logger( argument ) {
            if ( this.config && this.config.debug ) {
                console.log( argument );
            }
        }

    }

    $.each(
        multisafepay_payment_component_gateways,
        function ( index, gateway ) {
            if (
                typeof window['payment_component_config_' + gateway] !== "undefined" &&
                (window['payment_component_config_' + gateway].api_token !== '')
            ) {
                new MultiSafepayPaymentComponent( window['payment_component_config_' + gateway], gateway );
            }
        }
    );

})( multisafepay_payment_component_gateways, jQuery );
