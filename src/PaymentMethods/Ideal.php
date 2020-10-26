<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods;

class Ideal extends Core {
    /**
     * MultiSafepay constructor.
     */
    public function __construct() {
        $this->setId('ideal')
            ->setMethodTitle('Ideal')
            ->setMethodDescription('Placeholder');
        parent::__construct();
    }
}
