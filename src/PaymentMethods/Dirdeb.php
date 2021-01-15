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

namespace MultiSafepay\WooCommerce\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Account;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\ValueObject\IbanNumber;

class Dirdeb extends BasePaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_dirdeb';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'DIRDEB';
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
        return __( 'SEPA Direct Debit', 'multisafepay' );
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
            __( 'Suitable for collecting funds from your customers bank account on a recurring basis by means of authorization. <br />Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay' ),
            'https://docs.multisafepay.com/payment-methods/banks/sepa-direct-debit/?utm_source=woocommerce&utm_medium=woocommerce-cms&utm_campaign=woocommerce-cms',
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
        return array( 'account_holder_name', 'account_holder_iban', 'emandate' );
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'dirdeb.png';
    }

    /**
     * @param array|null $data
     * @return Account
     */
    public function get_gateway_info( array $data = null ): GatewayInfoInterface {

        $gateway_info = new Account();

        if ( isset( $_POST[ $this->id . '_account_holder_iban' ] ) ) {
            $gateway_info->addAccountId( new IbanNumber( $_POST[ $this->id . '_account_holder_iban' ] ) );
        }

        if ( isset( $_POST[ $this->id . '_account_holder_iban' ] ) ) {
            $gateway_info->addAccountHolderIban( new IbanNumber( $_POST[ $this->id . '_account_holder_iban' ] ) );
        }

        if ( isset( $_POST[ $this->id . '_emandate' ] ) ) {
            $gateway_info->addEmanDate( $_POST[ $this->id . '_emandate' ] );
        }

        if ( isset( $_POST[ $this->id . '_account_holder_name' ] ) ) {
            $gateway_info->addAccountHolderName( $_POST[ $this->id . '_account_holder_name' ] );
        }

        return $gateway_info;
    }

}
