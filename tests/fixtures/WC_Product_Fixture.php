<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Tests\Fixtures;

use WC_Product;
use WP_UnitTestCase;

/**
 * Class WC_ProductFixture
 *
 * @package MultiSafepay\WooCommerce\Tests\Fixtures
 */
class WC_Product_Fixture extends WP_UnitTestCase {

    /**
     * @var float
     */
    private $product_price;

    /**
     * @var int
     */
    private $product_quantity;

    /**
     * @var float
     */
    private $product_tax_rate;

    /**
     * WC_ProductFixture constructor.
     *
     * @param float $product_price
     * @param int $product_quantity
     * @param float $product_tax_rate
     */
    public function __construct(  float $product_price, int $product_quantity, float $product_tax_rate ) {
        $this->product_price      = $product_price;
        $this->product_quantity   = $product_quantity;
        $this->product_tax_rate   = $product_tax_rate;
    }

    public function get_wc_product_mock() {
        $wc_product =  $this->getMockBuilder( 'WC_Product' )
                            ->disableOriginalConstructor()
                            ->setMethods( array( 'get_price', 'get_subtotal', 'get_total_tax' ) )
                            ->getMock();
        $wc_product->method( 'get_price' )->willReturn( $this->product_price );
        $wc_product->method( 'get_subtotal' )->willReturn( ( $this->product_price * $this->product_quantity) );
        $wc_product->method( 'get_total_tax' )->willReturn( $this->product_price * ($this->product_tax_rate / 100 ) );
        return $wc_product;
    }



}
