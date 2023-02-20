<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;

class PayAfterDeliveryInstallments extends BasePaymentMethod {

    /**
     * @var bool
     */
    protected $has_configurable_payment_component = true;

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_payafterdelivery_installments';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'BNPL_INSTM';
    }

    /**
     * @return string
     */
    public function get_payment_method_type(): string {
        return 'redirect';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return __( 'Pay After Delivery Installments', 'multisafepay' );
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
        /* translators: %2$: The payment method title */
            __( 'Pay After Delivery - Instalments (Betaal in Termijnen) is MultiSafepay\'s own BNPL method for increasing customer confidence and conversion. MultiSafepay prefinances you, bears the risk, and guarantees settlement. Pay After Delivery allows customers to pay for orders in 3 equal installments. <br />Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay' ),
            'https://docs.multisafepay.com',
            $this->get_payment_method_title()
        );
        return $method_description;
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'pay-after-delivery-installments.png';
    }

}
