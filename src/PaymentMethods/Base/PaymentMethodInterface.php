<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;

/**
 * Interface PaymentMethodInterface
 *
 * @package MultiSafepay\WooCommerce\PaymentMethods
 */
interface PaymentMethodInterface {

    /**
     * Get the ID of the payment method
     *
     * @return string
     */
    public function get_payment_method_id(): string;

    /**
     * Get the code of the payment method
     *
     * @return string
     */
    public function get_payment_method_code(): string;

    /**
     * Get the method type, should be 'direct' or 'redirect'
     *
     * @return string
     */
    public function get_payment_method_type(): string;

    /**
     * Get the title that is shown in the backend
     *
     * @return string
     */
    public function get_payment_method_title(): string;

    /**
     * Get the method description in the backend
     *
     * @return string
     */
    public function get_payment_method_description(): string;

    /**
     * Add extra settings to a gateway
     *
     * @return array
     */
    public function add_form_fields();

    /**
     * Get has fields
     *
     * @return boolean
     */
    public function has_fields(): bool;

    /**
     * Add custom checkout fields by id
     *
     * @return array
     */
    public function get_checkout_fields_ids(): array;

    /**
     * Add icon to a gateway
     *
     * @return string
     */
    public function get_payment_method_icon(): string;

    /**
     * Add gatewayinfo to request
     *
     * @param array|null $data
     * @return GatewayInfoInterface
     */
    public function get_gateway_info( array $data = null): GatewayInfoInterface;

    /**
     * Check if the gateway info is complete, otherwise you can perform custom actions
     *
     * @param GatewayInfoInterface $gateway_info
     * @return boolean
     */
    public function validate_gateway_info( GatewayInfoInterface $gateway_info): bool;

}
