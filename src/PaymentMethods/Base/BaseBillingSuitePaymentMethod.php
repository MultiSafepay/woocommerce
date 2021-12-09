<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
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
     *
     * @return Meta
     */
    public function get_gateway_info( array $data = null ): GatewayInfoInterface {

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
