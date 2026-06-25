<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

use WC_Tax;

/**
 * Class TaxUtil
 */
class TaxUtil {

    /**
     * Returns the effective tax rate for multiple tax rates.
     *
     * This is used when WooCommerce returns a taxable product price excluding tax as 0,
     * so the effective rate can be derived without dividing by zero and while still
     * respecting compound tax rules.
     *
     * @param array $tax_rates
     * @return float
     */
    public static function get_effective_tax_rate( array $tax_rates ): float {
        return (float) array_sum( WC_Tax::calc_tax( 100, $tax_rates, false ) );
    }
}
