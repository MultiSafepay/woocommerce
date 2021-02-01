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

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\ValueObject\BankAccount;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use MultiSafepay\ValueObject\Date;
use MultiSafepay\ValueObject\Gender;

abstract class BaseBillingSuitePaymentMethod extends BasePaymentMethod {

    /**
     * @return boolean
     */
    public function has_fields(): bool {
        return true;
    }

    /**
     * @param array|null $data
     * @return Meta
     */
    public function get_gateway_info( array $data = null ): GatewayInfoInterface {
        if (
            ( isset( $_POST['woocommerce-process-checkout-nonce'] ) && wp_verify_nonce( $_POST['woocommerce-process-checkout-nonce'], 'woocommerce-process_checkout' ) ) ||
            ( isset( $_POST['woocommerce-pay-nonce'] ) && wp_verify_nonce( $_POST['woocommerce-pay-nonce'], 'woocommerce-pay' ) )
        ) {

            $gateway_info = new Meta();

            if ( isset( $_POST[ $this->id . '_gender' ] ) ) {
                $gateway_info->addGender( new Gender( $_POST[ $this->id . '_gender' ] ) );
            }

            if ( isset( $_POST[ $this->id . '_salutation' ] ) ) {
                $gateway_info->addGender( new Gender( $_POST[ $this->id . '_salutation' ] ) );
            }

            if ( isset( $_POST[ $this->id . '_birthday' ] ) ) {
                $gateway_info->addBirthday( new Date( $_POST[ $this->id . '_birthday' ] ) );
            }

            if ( isset( $_POST[ $this->id . '_bank_account' ] ) ) {
                $gateway_info->addBankAccount( new BankAccount( $_POST[ $this->id . '_bank_account' ] ) );
            }

            if ( isset( $data ) && ! empty( $data['order_id'] ) ) {
                $order = wc_get_order( $data['order_id'] );
                $gateway_info->addEmailAddress( new EmailAddress( $order->get_billing_email() ) );
                $gateway_info->addPhone( new PhoneNumber( $order->get_billing_phone() ) );
            }

            return $gateway_info;
        }
    }

}
