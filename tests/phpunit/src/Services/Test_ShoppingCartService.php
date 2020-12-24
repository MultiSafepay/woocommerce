<?php declare(strict_types=1);

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

use MultiSafepay\WooCommerce\Services\ShoppingCartService;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;

class Test_ShoppingCartService extends WP_UnitTestCase {

    private $wc_order;

    public function setUp() {

	    $items                      = array();
        $product_id                 = 11;
        $product_variation_id       = 0;
        $product_name               = 'Vneck Tshirt';
        $product_price              = 18.00;
        $product_quantity           = 2;
        $product_tax                = 3.78;
        $product_taxes              = array('total' => array('4' => 6.804), 'subtotal' => array('4' => 7.56));
        $fee                        = 3.30;
        $fee_total_tax              = 0.693;
        $fee_taxes                  = array('total' => array('4' => 0.693));
        $coupon_discount            = 3.6;
        $coupon_discount_tax        = 0.756;
        $coupon_data                = array();
        $coupon_data['code']        = '10-OFF';
        $shipping_total             = 4.99;
        $shipping_tax               = 1.0479;
        $shipping_taxes             = array('total' => array('4' => 1.0479));

		// Remove all tax classes.
	    $tax_classes = WC_Tax::get_tax_classes();
		foreach ($tax_classes as $tax_class) {
			WC_Tax::delete_tax_class_by('name', $tax_class);
		}

		// Tax Class.
	    $tax_class = WC_Tax::create_tax_class('Tax Class Name', 'tax-class-name');

		// Tax Rate
	    $tax_rate_data = array(
	        'tax_rate_country' => '*',
	        'tax_rate_state' => '*',
	        'tax_rate' => 21,
	        'tax_rate_name' => 'Tax Rate Name',
	        'tax_rate_priority' => 1,
	        'tax_rate_compound' => 0,
	        'tax_rate_shipping' => 1,
	        'tax_rate_order' => 0,
	        'tax_rate_class' => 'tax-class-name'
	    );
        $tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate_data );
	    add_filter('woocommerce_get_tax_location', function($location, $tax_class, $customer) { return array( 'NL', '', '1033 SC', 'Amsterdam' ); }, 10, 3);

	    // Item WC_Product Object
        $wc_product =  $this->getMockBuilder( 'WC_Product' )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_price', 'get_subtotal', 'get_total_tax' ) )
            ->getMock();
        $wc_product->method( 'get_price' )->willReturn( $product_price );
        $wc_product->method( 'get_subtotal' )->willReturn( ($product_price * $product_quantity) );
        $wc_product->method( 'get_total_tax' )->willReturn( $product_tax );

        // WC_Order_Item_Product Object
        $wc_order_item_product =  $this->getMockBuilder( 'WC_Order_Item_Product' )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_id', 'get_name', 'get_type', 'get_quantity', 'get_subtotal', 'get_total_tax', 'get_product', 'get_product_id', 'get_variation_id', 'get_taxes', 'get_tax_class' ) )
            ->getMock();
        $wc_order_item_product->method( 'get_id' )->willReturn( $product_id );
        $wc_order_item_product->method( 'get_name' )->willReturn( $product_name );
        $wc_order_item_product->method( 'get_type' )->willReturn( 'line_item' );
        $wc_order_item_product->method( 'get_quantity' )->willReturn( $product_quantity );
        $wc_order_item_product->method( 'get_subtotal' )->willReturn( ( $product_price * $product_quantity ) ) ;
        $wc_order_item_product->method( 'get_total_tax' )->will($this->returnValue($product_tax));
        $wc_order_item_product->method( 'get_product' )->willReturn( $wc_product );
        $wc_order_item_product->method( 'get_product_id' )->willReturn( $product_id );
        $wc_order_item_product->method( 'get_variation_id' )->willReturn( $product_variation_id );
        $wc_order_item_product->method( 'get_taxes' )->willReturn( $product_taxes );
        $wc_order_item_product->method( 'get_tax_class' )->willReturn( 'tax-class-name' );
        $items[] = $wc_order_item_product;

        // Coupon
        $wc_order_item_coupon =  $this->getMockBuilder( 'WC_Order_Item_Coupon' )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_id', 'get_name', 'get_data', 'get_discount', 'get_discount_tax', 'get_order' ) )
            ->getMock();
        $wc_order_item_coupon->method( 'get_id' )->willReturn( 45 );
        $wc_order_item_coupon->method( 'get_name' )->willReturn( 'Coupon 10-OFF' );
        $wc_order_item_coupon->method( 'get_data' )->willReturn( $coupon_data );
        $wc_order_item_coupon->method( 'get_discount' )->willReturn( $coupon_discount );
        $wc_order_item_coupon->method( 'get_discount_tax' )->willReturn( $coupon_discount_tax );

        // WC_Order_Item_Shipping
        $wc_order_item_shipping =  $this->getMockBuilder( 'WC_Order_Item_Shipping' )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_id', 'get_name', 'get_total', 'get_taxes' ) )
            ->getMock();
        $wc_order_item_shipping->method( 'get_id' )->willReturn( 99 );
        $wc_order_item_shipping->method( 'get_name' )->willReturn( 'Shipping' );
        $wc_order_item_shipping->method( 'get_total' )->willReturn( $shipping_total );
        $wc_order_item_shipping->method( 'get_taxes' )->willReturn( $shipping_taxes );

        // WC_Order_Item_Fee
        $wc_order_item_fee =  $this->getMockBuilder( 'WC_Order_Item_Fee' )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_id', 'get_name', 'get_type', 'get_quantity', 'get_total', 'get_total_tax', 'get_product_id', 'get_taxes' ) )
            ->getMock();
        $wc_order_item_fee->method( 'get_id' )->willReturn( 17 );
        $wc_order_item_fee->method( 'get_name' )->willReturn( 'Fee Name' );
        $wc_order_item_fee->method( 'get_type' )->willReturn( 'fee' );
        $wc_order_item_fee->method( 'get_quantity' )->willReturn( 1 );
        $wc_order_item_fee->method( 'get_total' )->willReturn( $fee );
        $wc_order_item_fee->method( 'get_total_tax' )->willReturn( $fee_total_tax );
        $wc_order_item_fee->method( 'get_product_id' )->willReturn( 17 );
        $wc_order_item_fee->method( 'get_taxes' )->willReturn( $fee_taxes );

        // Order
        $this->wc_order =  $this->getMockBuilder( 'WC_Order' )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_shipping_total',  'get_shipping_tax', 'get_items' ) )
            ->getMock();
        $this->wc_order->method('get_shipping_total')->will($this->returnValue($shipping_total));
        $this->wc_order->method('get_shipping_tax')->will($this->returnValue($shipping_tax));

        // Return WC_Order mock in WC_Order_Item_Coupon
        $wc_order_item_coupon->method( 'get_order' )->willReturn( $this->wc_order );

        // Consecutive calls for WC_Order->get_items()
        $this->wc_order->method( 'get_items' )->withConsecutive( array('line_item'), array('coupon'), array('line_item'), array('shipping'), array('fee') )
            ->willReturnOnConsecutiveCalls( $items, array( $wc_order_item_coupon ), $items, array($wc_order_item_shipping), array($wc_order_item_fee) );

    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\ShoppingCartService::create_shopping_cart
     */
    public function test_create_shopping_cart_has_keys() {
        $shopping_cart_service = new ShoppingCartService();
        $shopping_cart = $shopping_cart_service->create_shopping_cart($this->wc_order, 'EUR');
        $this->assertInstanceOf( ShoppingCart::class, $shopping_cart);

        $output = $shopping_cart->getData();

        $this->assertIsArray($output);
        $this->assertIsArray($output['items']);
        foreach ($output['items'] as $item) {
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('unit_price', $item);
            $this->assertArrayHasKey('currency', $item);
            $this->assertArrayHasKey('quantity', $item);
            $this->assertArrayHasKey('merchant_item_id', $item);
            $this->assertArrayHasKey('tax_table_selector', $item);
            $this->assertArrayHasKey('merchant_item_id', $item);
            $this->assertIsArray($item['weight']);
            $this->assertArrayHasKey('unit', $item['weight']);
            $this->assertArrayHasKey('value', $item['weight']);
        }
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\ShoppingCartService::create_shopping_cart
     */
    public function test_create_shopping_cart_has_values() {
        $shopping_cart_service = new ShoppingCartService();
        $shopping_cart = $shopping_cart_service->create_shopping_cart($this->wc_order, 'EUR');
        $output = $shopping_cart->getData();

        $product_item       = $output['items'][0];
        $product_coupon     = $output['items'][1];
        $product_shipping   = $output['items'][2];
        $product_fee        = $output['items'][3];

        $this->assertEquals( 'Vneck Tshirt', $product_item['name'] );
        $this->assertEquals( '', $product_item['description'] );
        $this->assertEquals( '18.0000000000', $product_item['unit_price'] );
        $this->assertEquals( 'EUR', $product_item['currency'] );
        $this->assertEquals( '2', $product_item['quantity'] );
        $this->assertEquals( '11', $product_item['merchant_item_id'] );
        $this->assertEquals( '21', $product_item['tax_table_selector'] );
        $this->assertEquals( '', $product_item['weight']['unit'] );
        $this->assertEquals( '', $product_item['weight']['value'] );

        $this->assertEquals( 'Coupon 10-OFF', $product_coupon['name'] );
        $this->assertEquals( '', $product_coupon['description'] );
        $this->assertEquals( '-3.6000000000', $product_coupon['unit_price'] );
        $this->assertEquals( 'EUR', $product_coupon['currency'] );
        $this->assertEquals( '1', $product_coupon['quantity'] );
        $this->assertEquals( '45', $product_coupon['merchant_item_id'] );
        $this->assertEquals( '21', $product_coupon['tax_table_selector'] );
        $this->assertEquals( '', $product_coupon['weight']['unit'] );
        $this->assertEquals( '', $product_coupon['weight']['value'] );

        $this->assertEquals( 'Shipping', $product_shipping['name'] );
        $this->assertEquals( '', $product_shipping['description'] );
        $this->assertEquals( '4.9900000000', $product_shipping['unit_price'] );
        $this->assertEquals( 'EUR', $product_shipping['currency'] );
        $this->assertEquals( '1', $product_shipping['quantity'] );
        $this->assertEquals( ShoppingCartService::MSP_SHIPPING_ITEM_CODE, $product_shipping['merchant_item_id'] );
        $this->assertEquals( '21', $product_shipping['tax_table_selector'] );
        $this->assertEquals( '', $product_shipping['weight']['unit'] );
        $this->assertEquals( '', $product_shipping['weight']['value'] );

        $this->assertEquals( 'Fee Name', $product_fee['name'] );
        $this->assertEquals( '', $product_fee['description'] );
        $this->assertEquals( '3.3000000000', $product_fee['unit_price'] );
        $this->assertEquals( 'EUR', $product_fee['currency'] );
        $this->assertEquals( '1', $product_fee['quantity'] );
        $this->assertEquals( '17', $product_fee['merchant_item_id'] );
        $this->assertEquals( '21', $product_fee['tax_table_selector'] );
        $this->assertEquals( '', $product_fee['weight']['unit'] );
        $this->assertEquals( '', $product_fee['weight']['value'] );

    }

}