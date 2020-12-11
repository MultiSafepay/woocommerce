<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;

class BaseGatewayInfo implements GatewayInfoInterface
{
    /**
     * @return array
     */
    public function getData(): array
    {
        return array();
    }
}

