<?php declare(strict_types=1);

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\WooCommerce\PaymentMethods;

use WC_Countries;
use WC_Order;
use WC_Payment_Gateway;

/**
 * Abstract Core class for each payment method
 *
 * @since   4.0.0
 */
abstract class Core extends WC_Payment_Gateway implements PaymentMethodInterface {

    /**
     * Construct for Core class.
     */
    public function __construct() {
        $this->supports = array('products', 'refunds');
        $this->add_form_fields();
        $this->init_settings();
        $this->enabled      = $this->get_option('enabled');
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->max_amount   = $this->get_option('max_amount');
        $this->countries    = $this->get_option('countries', array());
        $this->errors       = array();
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options') );
    }

    /**
     * Sets the method description
     *
     * @param string $value
     * @return mixed|void
     */
    public function set_method_description( string $value = '' ) {
        $general_tab = admin_url('admin.php?page=multisafepay-settings&tab=general');
        $common_message = 'You can configure the <a href="%s">common settings of the MultiSafepay payment methods</a> clicking on the link';
        $method_description = sprintf( __($common_message, 'multisafepay'), $general_tab );
        if( !empty( $value ) ) {
            $method_description .= sprintf( __( '<p>%s</p>', 'multisafepay'), $value );
        }
        $this->method_description = $method_description;
    }

    /**
     * Return an array of allowed countries defined in WooCommerce Settings.
     *
     * @return array
     */
    private function get_countries(): array {
        $countries = new WC_Countries();
        $allowed_countries = $countries->get_allowed_countries();
        return $allowed_countries;
    }

    /**
     * Define the form option - settings fields.
     *
     * @return  void
     */
    public function add_form_fields(): void {
        $this->form_fields = array(
            'enabled' => array(
                'title'         => 'Enable/Disable',
                'label'         => 'Enable ' . $this->get_method_title() .' Gateway',
                'type'          => 'checkbox',
                'description'   => __( 'This controls the title which the user sees during checkout.', 'multisafepay' ),
                'default'       => 'no'
            ),
            'title' => array(
                'title'         => 'Title',
                'type'          => 'text',
                'default'       => $this->get_method_title(),
            ),
            'description' => array(
                'title'         => __( 'Description', 'multisafepay' ),
                'type'          => 'textarea',
                'description'   => __( 'This controls the description which the user sees during checkout.', 'multisafepay' ),
                'default'       => ''
            ),
            'max_amount' => array(
                'title'         => __( 'Max Amount', 'multisafepay' ),
                'type'          => 'text',
                'description'   => __( 'This payment method is not shown in the checkout if the order total exceeds a certain amount. Leave black for no restrictions.', 'multisafepay' ),
                'default'       => $this->get_option('max_amount', 0),
            ),
            'countries' => array(
                'title'         => __( 'Country', 'multisafepay' ),
                'type'          => 'multiselect',
                'description'   => __( 'If you select one or more countries, this payment method won\'t show in the checkout page, if the payment address`s country of the customer match with the selected values. Leave black for no restrictions.', 'multisafepay' ),
                'desc_tip'      => __( 'For most operating system and configurations, you must hold Ctrl + D or Cmd + D on your keyboard, to select more than one value.', 'multisafepay' ),
                'options'       => $this->get_countries(),
                'default'       => $this->get_option('countries', array()),
            )
        );
    }

    /**
     * Sets the id for this payment method
     *
     * @param   string $value
     * @return  string
     */
    public function set_id( string $value ): object {
        $this->id = $value;
        return $this;
    }

    /**
     * Sets the icon for this payment method
     *
     * @param   string  $value
     * @return  string
     */
    public function set_icon( string $value ): object {
        $this->icon = esc_url( plugins_url( '/assets/public/img/' .  $value, dirname(__DIR__ ) ) );
        return $this;
    }

    /**
     * Sets the payment method title
     *
     * @param   string  $value
     * @return  string
     */
    public function set_method_title( string $value ): object {
        $this->method_title = $value;
        return $this;
    }

    /**
     * Process the payment and return the result.
     *
     * @param   integer $order_id   Order ID.
     * @return  array|mixed|void
     */
    public function process_payment( $order_id ) {
        // Something like:
        global $woocommerce;
        $order = new WC_Order( $order_id );
        $order->update_status('completed', __( 'Completed', 'multisafepay' ));
        $woocommerce->cart->empty_cart();
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url( $order )
        );
    }


    /**
     * Process the refund.
     *
     * @todo This function needs more work to process the refund.
     *
     * @param   integer $order_id   Order ID.
     * @param   float  $amount     Amount to be refunded.
     * @param   string $reason     Reason description.
     * @return  boolean
     */
    public function process_refund( $order_id, $amount = null, $reason = ''): bool {
        return false;
    }

    /**
     * This validates that the API Key has been setup properly
     * check SDK, and check if the gateway is enable for the merchant.
     *
     * @todo This function needs more work checking if API key works on the SDK.
     *
     * @param   string  $key
     * @param   string  $value
     * @return  string

     */
    public function validate_enabled_field( $key, $value ): string {
        return $value !== null ? 'yes' : 'no';
    }

}