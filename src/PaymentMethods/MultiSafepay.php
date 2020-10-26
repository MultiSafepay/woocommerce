<?php declare(strict_types=1);


namespace MultiSafepay\WooCommerce\PaymentMethods;


class MultiSafepay extends Core {
    /**
     * MultiSafepay constructor.
     */
    public function __construct() {
        $this->setId('multisafepay')
            ->setMethodTitle('MultiSafepay')
            ->setMethodDescription('Placeholder');
        parent::__construct();
    }
}
