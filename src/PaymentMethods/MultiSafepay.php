<?php


namespace MultiSafepay\WooCommerce\PaymentMethods;


class MultiSafepay extends Core
{
    public function __construct()
    {
        $this->setId('multisafepay')
            ->setMethodTitle('MultiSafepay')
            ->setMethodDescription('Placeholder');
        parent::__construct();
    }
}
