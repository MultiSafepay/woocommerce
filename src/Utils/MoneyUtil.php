<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

use MultiSafepay\ValueObject\Money;

/**
 * Class MoneyUtil
 */
class MoneyUtil {
    public const DEFAULT_CURRENCY_CODE = 'EUR';

    /**
     * @param float  $amount
     * @param string $currency_code
     * @return Money
     */
    public static function create_money( float $amount, string $currency_code = self::DEFAULT_CURRENCY_CODE ): Money {
        return new Money( self::price_to_cents( $amount ), $currency_code );
    }

    /**
     * @param float $price
     * @return float
     */
    private static function price_to_cents( float $price ) {
        return $price * 100;
    }
}
