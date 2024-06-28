<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;

/**
 * Class BaseGiftCardPaymentMethod
 *
 * @package MultiSafepay\WooCommerce\PaymentMethods\Base
 */
class BaseBrandedPaymentMethod extends BasePaymentMethod {

    /**
     * @var array
     */
    protected $brand;

    /**
     * @param PaymentMethod $payment_method
     * @param array         $brand
     */
    public function __construct( PaymentMethod $payment_method, array $brand ) {
        $this->brand = $brand;
        parent::__construct( $payment_method );
    }

    /**
     * @return string
     */
    public function get_payment_method_gateway_code(): string {
        return $this->payment_method->getId();
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return $this->brand['name'];
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return $this->brand['icon_urls']['large'];
    }

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return PaymentMethodService::get_legacy_woocommerce_payment_gateway_ids( $this->brand['id'] );
    }


}
