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
use MultiSafepay\WooCommerce\Tests\Fixtures\TaxesFixture;
use MultiSafepay\WooCommerce\Tests\Fixtures\WC_Order_Item_Product_Fixture;
use MultiSafepay\WooCommerce\Tests\Fixtures\WC_Order_item_Coupon_Fixture;
use MultiSafepay\WooCommerce\Tests\Fixtures\WC_Order_Item_Shipping_Fixture;
use MultiSafepay\WooCommerce\Tests\Fixtures\WC_Order_Item_Fee_Fixture;
use MultiSafepay\WooCommerce\Tests\Fixtures\WC_Order_Fixture;

class Test_ShoppingCartService extends WP_UnitTestCase {

    private $wc_order;

    public function setUp() {

        parent::setUp();
        $product_id                 = 11;
        $product_name               = 'Vneck Tshirt';
        $product_price              = 18.00;
        $product_tax_rate           = 21;
        $product_quantity           = 2;
        $discount_percentage        = 10;
        $shipping_total             = 4.99;
        $shipping_tax_rate          = 21;

        // Set taxes.
        $tax_fixture = new TaxesFixture( 'Tax Rate Name', 21, 'Tax Class Name' );
        $tax_fixture->register_tax_rate();

        // Set Products.
        $wc_order_item_product = (new WC_Order_Item_Product_Fixture( $product_id, $product_name, $product_price, $product_quantity, $product_tax_rate, $discount_percentage))->get_wc_order_item_product_mock();
        $wc_order_item_coupon = (new WC_Order_item_Coupon_Fixture( 'Coupon 10-OFF', '10-OFF', 'percentage', 10, $product_price, $product_quantity ))->get_wc_order_item_coupon_mock();
        $wc_order_item_shipping = (new WC_Order_Item_Shipping_Fixture( $shipping_total, $shipping_tax_rate ))->get_wc_order_item_shipping_mock();
        $wc_order_item_fee = (new WC_Order_Item_Fee_Fixture( 3.30, 21 ))->get_wc_order_item_fee_mock();
        $this->wc_order = (new WC_Order_Fixture( $shipping_total, $shipping_tax_rate ))->get_wc_order_mock();

        // Return WC_Order mock in WC_Order_Item_Coupon
        $wc_order_item_coupon->method( 'get_order' )->willReturn( $this->wc_order );

        // Consecutive calls for WC_Order->get_items()
        $this->wc_order->method( 'get_items' )->withConsecutive( array('line_item'), array('coupon'),  array('shipping'), array('fee') )
            ->willReturnOnConsecutiveCalls( array( $wc_order_item_product ), array( $wc_order_item_coupon ), array($wc_order_item_shipping), array($wc_order_item_fee) );

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

    public function tearDown() {
        TaxesFixture::delete_tax_classes();
    }

}
