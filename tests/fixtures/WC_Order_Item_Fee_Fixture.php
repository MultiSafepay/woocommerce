<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Tests\Fixtures;

use WC_Order_Item_Fee;
use WP_UnitTestCase;

/**
 * Class WC_ProductFixture
 *
 * @package MultiSafepay\WooCommerce\Tests\Fixtures
 */
class WC_Order_Item_Fee_Fixture extends WP_UnitTestCase {

    /**
     * @var float
     */
    private $fee_total;

    /**
     * @var float
     */
    private $fee_tax_rate;

    /**
     * WC_Order_Item_Fee_Fixture constructor.
     *
     * @param float $fee_total
     * @param float $fee_tax_rate
     */
    public function __construct( float $fee_total,  float $fee_tax_rate ) {
        $this->fee_total          = $fee_total;
        $this->fee_tax_rate       = $fee_tax_rate;

    }

    public function get_wc_order_item_fee_mock( $wc_order ) {
        $wc_order_item_fee =  $this->getMockBuilder( 'WC_Order_Item_Fee' )
                                   ->disableOriginalConstructor()
                                   ->setMethods( array( 'get_id', 'get_name', 'get_type', 'get_quantity', 'get_total', 'get_total_tax', 'get_product_id', 'get_taxes', 'get_order' ) )
                                   ->getMock();
        $wc_order_item_fee->method( 'get_id' )->willReturn( 17 );
        $wc_order_item_fee->method( 'get_name' )->willReturn( 'Fee Name' );
        $wc_order_item_fee->method( 'get_type' )->willReturn( 'fee' );
        $wc_order_item_fee->method( 'get_quantity' )->willReturn( 1 );
        $wc_order_item_fee->method( 'get_total' )->willReturn( $this->fee_total );
        $wc_order_item_fee->method( 'get_total_tax' )->willReturn( $this->fee_total * ( $this->fee_tax_rate / 100 ) );
        $wc_order_item_fee->method( 'get_product_id' )->willReturn( 17 );
        if($this->fee_tax_rate > 0) {
            $wc_order_item_fee->method( 'get_taxes' )->willReturn( array( 'total' => array('4' => $this->fee_total * ( $this->fee_tax_rate / 100 ) ) ) );
        }
        $wc_order_item_fee->method( 'get_taxes' )->willReturn( array( 'total' => array() ) );
        $wc_order_item_fee->method( 'get_order' )->willReturn( $wc_order );
        return $wc_order_item_fee;
    }

}
