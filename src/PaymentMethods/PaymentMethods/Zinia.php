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
        return ( $this->get_option( 'direct', 'yes' ) === 'yes' ) ? 'direct' : 'redirect';
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
        return '';
    }

    /**
     * @return boolean
     */
    public function has_fields(): bool {
        if ( $this->is_payment_component_enable() ) {
            return true;
        }
        return ( $this->get_option( 'direct', 'yes' ) === 'yes' ) ? true : false;
    }

    /**
     * @return array
     */
    public function add_form_fields(): array {
        $form_fields           = parent::add_form_fields();
        $form_fields['direct'] = array(
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
        if ( $this->is_payment_component_enable() ) {
            return array();
        }
        return array( 'birthday', 'gender' );
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'zinia.png';
    }

    /**
     * @param array|null $data
     *
     * @return GatewayInfoInterface
     */
    public function get_gateway_info( array $data = null ): GatewayInfoInterface {
        if ( $this->is_payment_component_enable() ) {
            return new BaseGatewayInfo();
        }
        return $this->get_gateway_info_meta( $data );
    }

}
