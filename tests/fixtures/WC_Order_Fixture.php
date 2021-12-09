<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Tests\Fixtures;

use WC_Order;
use WP_UnitTestCase;

/**
 * Class WC_ProductFixture
 *
 * @package MultiSafepay\WooCommerce\Tests\Fixtures
 */
class WC_Order_Fixture extends WP_UnitTestCase {

    /**
     * @var float
     */
    private $shipping_total;

    /**
     * @var float
     */
    private $shipping_tax_rate;

    /**
     * WC_Order_Fixture constructor.
     *
     * @param float $shipping_total
     * @param float $shipping_tax_rate
     */
    public function __construct( float $shipping_total,  float $shipping_tax_rate ) {
        $this->shipping_total          = $shipping_total;
        $this->shipping_tax_rate       = $shipping_tax_rate;

    }

    public function get_wc_order_mock() {
        $wc_order =  $this->getMockBuilder( 'WC_Order' )
                                ->disableOriginalConstructor()
                                ->setMethods( array( 'get_shipping_total',  'get_shipping_tax', 'get_items' ) )
                                ->getMock();
        $wc_order->method('get_shipping_total')->will( $this->returnValue( $this->shipping_total ) );
        $wc_order->method('get_shipping_tax')->will( $this->returnValue( $this->shipping_total * ( $this->shipping_tax_rate / 100 ) ) );
        return $wc_order;
    }

}
