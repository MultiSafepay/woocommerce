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
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta as MetaGatewayInfo;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\BaseGatewayInfo;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use MultiSafepay\ValueObject\Date;
use MultiSafepay\ValueObject\Gender;

class In3 extends BaseBillingSuitePaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_in3';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'IN3';
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
        return __( 'in3', 'multisafepay' );
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
            /* translators: %2$: The payment method title */
            __( 'Conveniently allows customers to make three seperate payments for a single purchase. <br />Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay' ),
            'https://docs.multisafepay.com/payment-methods/billing-suite/in3/?utm_source=woocommerce&utm_medium=woocommerce-cms&utm_campaign=woocommerce-cms',
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
    public function add_form_fields(): array {
        $form_fields                          = parent::add_form_fields();
        $form_fields['min_amount']['default'] = '100';
        $form_fields['max_amount']['default'] = '3000';
        return $form_fields;
    }

    /**
     * @return array
     */
    public function get_checkout_fields_ids(): array {
        return array( 'salutation', 'birthday' );
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'in3.png';
    }

    /**
     * Validate_fields
     *
     * @return  boolean
     */
    public function validate_fields(): bool {
        return true;
    }

    /**
     * @param array|null $data
     * @return MetaGatewayInfo
     */
    public function get_gateway_info( array $data = null ): GatewayInfoInterface {

        $gateway_info = new MetaGatewayInfo();

        if ( isset( $_POST[ $this->id . '_salutation' ] ) && '' !== $_POST[ $this->id . '_salutation' ] ) {
            $gateway_info->addGender( new Gender( $_POST[ $this->id . '_salutation' ] ) );
        }

        if ( isset( $_POST[ $this->id . '_birthday' ] ) && '' !== $_POST[ $this->id . '_birthday' ] ) {
            $gateway_info->addBirthday( new Date( $_POST[ $this->id . '_birthday' ] ) );
        }

        if ( isset( $data ) && ! empty( $data['order_id'] ) ) {
            $order = wc_get_order( $data['order_id'] );
            $gateway_info->addEmailAddress( new EmailAddress( $order->get_billing_email() ) );
            $gateway_info->addPhone( new PhoneNumber( $order->get_billing_phone() ) );
        }

        if ( ! empty( $gateway_info->getData() ) ) {
            return $gateway_info;
        }

        if ( empty( $gateway_info->getData() ) ) {
            return new BaseGatewayInfo();
        }

    }

    /**
     * Check if issuer_id has been set
     *
     * @param GatewayInfoInterface $gateway_info
     * @return boolean
     */
    public function validate_gateway_info( GatewayInfoInterface $gateway_info ): bool {
        $data = $gateway_info->getData();
        if ( empty( $data['gender'] ) || empty( $data['birthday'] ) ) {
            $this->type = 'redirect';
            return false;
        }
        return true;
    }

}
