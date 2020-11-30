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

class Santander extends BasePaymentMethod
{
    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_santander';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'SANTANDER';
    }

    /**
     * @return string
     */
    public function get_payment_method_type(): string {
        return 'direct';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return __('Santander Consumer Finance | Pay per month', 'multisafepay');
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
            __('Allows customers to pay for online purchases as a one-off post-payment or in monthly installments. <br />Read more about <a href="%s" target="_blank">%s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay'),
            'https://docs.multisafepay.com/payment-methods/billing-suite/betaalplan/?utm_source=woocommerce&utm_medium=woocommerce-cms&utm_campaign=woocommerce-cms',
            $this->get_payment_method_title()
        );
        return $method_description;
    }

    /**
     * @return boolean
     */
    public function has_fields(): bool {
        return true;
    }

    /**
     * @return array
     */
    public function get_checkout_fields_ids(): array {
        return array( 'sex', 'birthday', 'bank_account' );
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'betaalplan.png';
    }

    /**
     * @return string
     */
    public function get_gateway_info(): string {
        return 'Meta';
    }

}