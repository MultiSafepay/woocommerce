<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGatewayInfo;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;

class Zinia extends BasePaymentMethod {

    /**
     * @var bool
     */
    protected $has_configurable_payment_component = true;

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_zinia';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'ZINIA';
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
        return __( 'Zinia', 'multisafepay' );
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
        /* translators: %2$: The payment method title */
            __( 'Boost your sales with Zinia. Grow your business by offering your customers the freedom to pay in 14 days or in 3 instalments, interest-free. Increase your average transaction value and boost sales by offering your customers a flexible payment method. Customers pay later, while you get paid upfront and in full. No risks for you and no interest for your customers. <br />Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay' ),
            'https://docs.multisafepay.com',
            $this->get_payment_method_title()
        );
        return $method_description;
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'zinia.png';
    }
}
