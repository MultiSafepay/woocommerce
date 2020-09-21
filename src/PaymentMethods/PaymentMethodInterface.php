<?php


namespace MultiSafepay\WooCommerce\PaymentMethods;


interface PaymentMethodInterface
{
    public function setId(string $value);
    public function setMethodTitle(string $value);
    public function process_payment($orderId);
    public function init_form_fields();
    public function setMethodDescription(string $value = '');
}
