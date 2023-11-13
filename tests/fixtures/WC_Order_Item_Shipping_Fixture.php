<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Tests\Fixtures;

use WC_Order_Item_Shipping;
use WP_UnitTestCase;

/**
 * Class WC_ProductFixture
 *
 * @package MultiSafepay\WooCommerce\Tests\Fixtures
 */
class WC_Order_Item_Shipping_Fixture extends WP_UnitTestCase {

    /**
     * @var float
     */
    private $shipping_total;

    /**
     * @var float
     */
    private $shipping_tax_rate;

    /**
     * WC_Order_Item_Shipping_Fixture constructor.
     *
     * @param float $shipping_total
     * @param float $shipping_tax_rate
     */
    public function __construct( float $shipping_total,  float $shipping_tax_rate ) {
        $this->shipping_total          = $shipping_total;
        $this->shipping_tax_rate       = $shipping_tax_rate;

    }

    public function get_wc_order_item_shipping_mock( $wc_order ) {
        $wc_order_item_shipping =  $this->getMockBuilder( 'WC_Order_Item_Shipping' )
                                        ->disableOriginalConstructor()
                                        ->setMethods( array( 'get_id', 'get_name', 'get_total', 'get_taxes', 'get_order' ) )
                                        ->getMock();
        $wc_order_item_shipping->method( 'get_id' )->willReturn( 99 );
        $wc_order_item_shipping->method( 'get_name' )->willReturn( 'Shipping' );
        $wc_order_item_shipping->method( 'get_total' )->willReturn( $this->shipping_total );
        $wc_order_item_shipping->method( 'get_taxes' )->willReturn( array('total' => array('4' => $this->shipping_total * ( $this->shipping_tax_rate / 100 ) ) ) );
        $wc_order_item_shipping->method( 'get_order' )->willReturn( $wc_order );
        return $wc_order_item_shipping;

    }

}
