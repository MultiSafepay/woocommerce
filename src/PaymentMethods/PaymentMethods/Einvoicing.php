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
 */

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseBillingSuitePaymentMethod;

class Einvoicing extends BaseBillingSuitePaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_einvoice';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'EINVOICE';
    }

    /**
     * @return string
     */
    public function get_payment_method_type(): string {
        return ( $this->get_option( 'direct', 'yes' ) === 'yes' ) ? 'direct' : 'redirect';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'E-Invoicing';
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
            /* translators: %2$: The payment method title */
            __( 'A flexible payment solution allowing businesses to take control and personalize consumer payments. <br />Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay' ),
            'https://docs.multisafepay.com/payment-methods/billing-suite/e-invoicing/?utm_source=woocommerce&utm_medium=woocommerce-cms&utm_campaign=woocommerce-cms',
            $this->get_payment_method_title()
        );
        return $method_description;
    }

    /**
     * @return boolean
     */
    public function has_fields(): bool {
        return ( $this->get_option( 'direct', 'yes' ) === 'yes' ) ? true : false;
    }

    /**
     * @return array
     */
    public function add_form_fields(): array {
        $form_fields                          = parent::add_form_fields();
        $form_fields['min_amount']['default'] = '15';
        $form_fields['max_amount']['default'] = '300';
        $form_fields['direct']                = array(
            'title'    => __( 'Transaction Type', 'multisafepay' ),
            /* translators: %1$: The payment method title */
            'label'    => sprintf( __( 'Enable direct %1$s', 'multisafepay' ), $this->get_payment_method_title() ),
            'type'     => 'checkbox',
            'default'  => 'yes',
            'desc_tip' => __( 'If enabled, additional information can be entered during WooCommerce checkout. If disabled, additional information will be requested on the MultiSafepay payment page.', 'multisafepay' ),
        );
        return $form_fields;
    }

    /**
     * @return array
     */
    public function get_checkout_fields_ids(): array {
        return array( 'birthday', 'bank_account' );
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'einvoice.png';
    }

}
