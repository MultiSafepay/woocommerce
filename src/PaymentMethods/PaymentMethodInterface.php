<?php declare(strict_types=1);
/**
 * codingStandardsIgnoreFile
 */
namespace MultiSafepay\WooCommerce\PaymentMethods;

interface PaymentMethodInterface
{
    /**
     * Set the ID of the payment method
     *
     * @param string $value
     * @return mixed
     */
    public function setId(string $value);

    /**
     * Set the title that is shown in the backend
     *
     * @param string $value
     * @return mixed
     */
    public function setMethodTitle(string $value);

    /**
     * How the payment should be handled by MultiSafepay
     *
     * @param $orderId
     * @return mixed
     */
    public function process_payment($orderId);

    /**
     * Add extra settings to a gateway
     *
     * @return mixed
     */
    public function addFormFields();

    /**
     * Set the method description in the backend
     *
     * @param string $value
     * @return mixed
     */
    public function setMethodDescription(string $value = '');
}
