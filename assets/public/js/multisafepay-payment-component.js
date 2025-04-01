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

    const FORM_SELECTOR           = 'form.checkout';
    const PAYMENT_METHOD_SELECTOR = 'ul.wc_payment_methods input[type=\'radio\'][name=\'payment_method\']';
    const FORM_BUTTON_SELECTOR    = '#place_order';

    class MultiSafepayPaymentComponent {

        payment_component                    = false;
        config                               = [];
        gateway                              = '';
        payment_component_container_selector = '';
        order_id                             = null;
        mandatory_field_changed              = false;
        qr_code_generated                    = false;
        qr_event_launched                    = false;

        constructor(config, gateway) {
            this.payment_component_container_selector = '#' + gateway + '_payment_component_container';
            this.payment_component                    = false;
            this.config                               = config;
            this.gateway                              = gateway;

            // Triggered when change the payment method selected
            $( document ).on( 'payment_method_selected', ( event ) => { this.on_payment_method_selected( event ); } );

            // Triggered when something changed in the checkout and the process to refresh everything is finished
            $( document ).on( 'updated_checkout', ( event ) => { this.on_updated_checkout( event ); } );

            // Triggered when the checkout loads
            $( document ).on( 'init_checkout', ( event ) => { this.on_init_checkout( event ); } );

            // Triggered when a user clicks on the 'submit' button of the checkout form
            $( document ).on( 'click', FORM_BUTTON_SELECTOR, ( event ) => { this.on_click_place_order( event ); } );

            // Triggered when a user changes a field in the checkout form and a payment method using QR is being used
            $( FORM_SELECTOR ).on(
                'change',
                'input, select, textarea',
                (event) => {
                    this.on_checkout_field_change( event );
                }
            );
        }

        on_checkout_field_change( event ) {
            this.logger( event.type );

            if ($( event.target ).attr( 'name' ) === 'payment_method' && this.config.qr_supported !== '1') {
                return;
            }

            if (this.config.qr_supported === '1' && this.is_selected()) {
                $( document.body ).trigger( 'updated_checkout' );
            }
        }

        on_payment_method_selected( event ) {
            this.logger( event.type );

            this.enable_place_order_button();

            if ( false === this.is_selected() ) {
                return;
            }

            if ( false === this.is_payment_component_gateway() ) {
                return;
            }

            this.maybe_init_payment_component();
        }

        on_updated_checkout( event ) {
            this.logger( event.type );

            if ( false === this.is_selected() ) {
                return;
            }

            if ( false === this.is_payment_component_gateway() ) {
                return;
            }

            this.refresh_payment_component_config();
        }

        on_init_checkout( event ) {
            this.logger( event.type );

            if ( false === this.is_selected() ) {
                return;
            }

            if ( false === this.is_payment_component_gateway() ) {
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
                        'action': 'refresh_payment_component_config',
                        'gateway_id': this.gateway,
                        'gateway': this.config.gateway,
                        'form_data': $( FORM_SELECTOR ).serialize(),
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
            return $( PAYMENT_METHOD_SELECTOR + ':checked' ).val() === this.gateway;
        }

        is_payment_component_gateway() {
            return $.inArray( $( PAYMENT_METHOD_SELECTOR + ':checked' ).val(), multisafepay_payment_component_gateways ) !== -1;
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

        set_multisafepay_qr_code_transaction( payload ) {
            this.logger( 'Getting QR Data via Ajax' );
            return new Promise(
                ( resolve, reject ) => {
                    $.ajax(
                        {
                            url: this.config.ajax_url,
                            type: 'POST',
                            data: {
                                'nonce': this.config.nonce,
                                'action': 'set_multisafepay_qr_code_transaction',
                                'gateway_id': this.gateway,
                                'payload': payload,
                                'form_data': $( FORM_SELECTOR ).serialize(),
                            },
                            success: function( response ) {
                                resolve( response );
                            }.bind( this ),
                            error: function( error ) {
                                this.logger( 'Error receiving QR Data: ' + JSON.stringify( error, null, 2 ) );
                                reject( error );
                            }.bind( this )
                        }
                    );
                }
            );
        }

        get_qr_order_redirect_url( order_id ) {
            this.logger( 'Getting redirect URL' );
            return new Promise(
                ( resolve, reject ) => {
                    $.ajax(
                        {
                            url: this.config.ajax_url,
                            type: 'POST',
                            data: {
                                'nonce': this.config.nonce,
                                'action': 'get_qr_order_redirect_url',
                                'gateway_id': this.gateway,
                                'order_id': order_id
                            },
                            success: function( response ) {
                                resolve( response );
                            }.bind( this ),
                            error: function( error ) {
                                this.logger( 'Error on get_qr_order_redirect_url AJAX: ' + JSON.stringify( error, null, 2 ) );
                                reject( error );
                            }.bind( this )
                        }
                    );
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
                    onLoad: state => {
                        this.logger( 'onLoad: ' + JSON.stringify( state, null, 2 ) );
                    },
                    onError: state => {
                        this.logger( 'onError: ' + JSON.stringify( state, null, 2 ) );
                    },
                    onValidation: state => {
                        if ( this.config.qr_supported === '1' && this.is_selected() && state.valid ) {
                            this.enable_place_order_button();
                            this.logger( 'onValidation: ' + JSON.stringify( state, null, 2 ) );
                        }
                        if ( this.config.qr_supported === '1' && this.is_selected() && ! state.valid ) {
                            this.disable_place_order_button();
                        }
                        this.logger( 'onValidation: ' + JSON.stringify( state, null, 2 ) );
                    },
                    onGetQR: state => {
                        this.logger( 'onGetQR Event: ' + JSON.stringify( state.orderData, null, 2 ) );
                        this.qr_code_generated = false;
                        this.qr_event_launched = true;
                        if ( state.orderData && state.orderData.payment_data && state.orderData.payment_data.payload ) {
                            this.set_multisafepay_qr_code_transaction( state.orderData.payment_data.payload ).then(
                                response => {
                                    this.logger( 'onGetQR - Response: ' + JSON.stringify( response, null, 2 ) );
                                    multisafepay_component.setQR( { order: response } );
                                    if ( response.order_id ) {
                                        this.order_id          = response.order_id;
                                        this.qr_code_generated = true;
                                        this.disable_place_order_button();
                                    }
                                }
                            );
                        }
                    },
                    onEvent: state => {
                        this.logger( 'onEvent: ' + JSON.stringify( state, null, 2 ) );
                        if ( ( state.type === 'check_status' ) && state.success && state.data.qr_status) {
                            if ( this.order_id !== null ) {
                                switch ( state.data.qr_status ) {
                                    case 'initialized':
                                        this.disable_place_order_button();
                                        break;
                                    case 'completed':
                                        this.get_qr_order_redirect_url( this.order_id ).then(
                                            response => {
                                                if ( response.success ) {
                                                    window.location.href = response.redirect_url;
                                                }
                                            }
                                        );
                                        break;
                                    case 'declined':
                                        this.get_qr_order_redirect_url( this.order_id ).then(
                                            response => {
                                                if ( response.success ) {
                                                    window.location.href = response.redirect_url;
                                                }
                                            }
                                        );
                                        break;
                                    default:
                                        this.logger( 'Unknown QR status: ' + state.data.qr_status );
                                        break;
                                }
                            }
                        }
                    }
                }
            );
            this.hide_loader();
        }

        reinit_payment_component() {
            this.init_payment_component();
        }

        maybe_init_payment_component() {
            // There is no way to know if the payment component exist or not; except for checking the DOM elements
            if ( $( this.payment_component_container_selector + ' > .msp-container-ui' ).length > 0) {
                return;
            }
            this.logger( 'Container exist' );
            this.init_payment_component();
        }

        show_loader() {
            $( this.payment_component_container_selector ).html( '<div class="loader-wrapper"><span class="loader"></span></span></div>' );
            this.disable_place_order_button();
        }

        hide_loader() {
            $( this.payment_component_container_selector + ' .loader-wrapper' ).remove();
            this.enable_place_order_button();
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

        disable_place_order_button() {
            $( FORM_BUTTON_SELECTOR ).prop( 'disabled', true );
        }

        enable_place_order_button() {
            $( FORM_BUTTON_SELECTOR ).prop( 'disabled', false );
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
