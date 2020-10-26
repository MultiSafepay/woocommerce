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

use WC_Payment_Gateway;

abstract class Core extends WC_Payment_Gateway implements PaymentMethodInterface {

    /**
     * Core constructor.
     */
    public function __construct() {
        $this->supports = ['products'];

        // Method with all the options fields
        $this->addFormFields();

        // Load the settings.
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * @param string $value
     * @return mixed|void
     */
    public function setMethodDescription( string $value = '' ) {
        $this->method_description = $value;
    }

    /**
     * @return mixed|void
     */
    public function addFormFields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable ' . $this->get_method_title() . ' Gateway',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'default' => $this->get_method_title(),
            )
        );
    }

    /**
     * @param string $value
     * @return $this|mixed
     */
    public function setId( string $value ) {
        $this->id = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this|mixed
     */
    public function setMethodTitle( string $value ) {
        $this->method_title = $value;
        return $this;
    }

    /**
     * @param $order_id
     * @return array|mixed|void
     */
    public function process_payment( $order_id ) {

    }

}
