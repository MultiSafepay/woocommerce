<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Tests\Fixtures;

use WC_Order_Item_Coupon;
use WP_UnitTestCase;

/**
 * Class WC_ProductFixture
 *
 * @package MultiSafepay\WooCommerce\Tests\Fixtures
 */
class WC_Order_item_Coupon_Fixture extends WP_UnitTestCase {

    /**
     * @var string
     */
    private $coupon_name;

    /**
     * @var string
     */
    private $coupon_code;

    /**
     * @var string
     */
    private $coupon_type;

    /**
     * @var string
     */
    private $coupon_discount;

    /**
     * @var float
     */
    private $product_price;

    /**
     * @var int
     */
    private $product_quantity;

    /**
     * WC_Order_item_Coupon_Fixture constructor.
     *
     * @param string $coupon_name
     * @param string $coupon_code
     * @param string $coupon_type
     * @param float $coupon_discount
     * @param float $product_price
     * @param int $product_quantity
     */
    public function __construct( string $coupon_name,  string $coupon_code, string $coupon_type, float $coupon_discount, float $product_price, int $product_quantity ) {
        $this->coupon_name          = $coupon_name;
        $this->coupon_code          = $coupon_code;
        $this->coupon_type          = $coupon_type;
        $this->coupon_discount      = $coupon_discount;
        $this->product_price        = $product_price;
        $this->product_quantity     = $product_quantity;
    }

    public function get_wc_order_item_coupon_mock() {
        $wc_order_item_coupon =  $this->getMockBuilder( 'WC_Order_Item_Coupon' )
                                      ->disableOriginalConstructor()
                                      ->setMethods( array( 'get_id', 'get_name', 'get_data', 'get_discount', 'get_discount_tax', 'get_order' ) )
                                      ->getMock();
        $wc_order_item_coupon->method( 'get_id' )->willReturn( 45 );
        $wc_order_item_coupon->method( 'get_name' )->willReturn( $this->coupon_name );
        $wc_order_item_coupon->method( 'get_data' )->willReturn( array( 'code' =>  $this->coupon_code ) );
        $wc_order_item_coupon->method( 'get_discount' )->willReturn( $this->get_coupon_discount() );
        $wc_order_item_coupon->method( 'get_discount_tax' )->willReturn( 0.756 );
        return $wc_order_item_coupon;
    }

    private function get_coupon_discount() {
        if($this->coupon_type === 'percentage') {
            return $this->product_price * $this->product_quantity * ( $this->coupon_discount / 100 );
        }
    }


}
