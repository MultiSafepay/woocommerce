<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Services\ShoppingCartService;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\WooCommerce\Tests\Fixtures\TaxesFixture;
use MultiSafepay\WooCommerce\Tests\Fixtures\WC_Order_Item_Product_Fixture;
use MultiSafepay\WooCommerce\Tests\Fixtures\WC_Order_Item_Shipping_Fixture;
use MultiSafepay\WooCommerce\Tests\Fixtures\WC_Order_Item_Fee_Fixture;
use MultiSafepay\WooCommerce\Tests\Fixtures\WC_Order_Fixture;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\ShippingItem;

class Test_ShoppingCartService extends WP_UnitTestCase {

    public function setUp() {
        update_option( 'woocommerce_calc_taxes', 'yes');
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\ShoppingCartService::create_shopping_cart
     */
    public function test_create_shopping_cart_has_keys() {
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
        $wc_order_item_product = (new WC_Order_Item_Product_Fixture( $product_id, $product_name, $product_price, $product_quantity, $product_tax_rate, $discount_percentage, sanitize_title('Tax Class Name')))->get_wc_order_item_product_mock();
        $wc_order_item_shipping = (new WC_Order_Item_Shipping_Fixture( $shipping_total, $shipping_tax_rate ))->get_wc_order_item_shipping_mock();
        $wc_order_item_fee = (new WC_Order_Item_Fee_Fixture( 3.30, 21 ))->get_wc_order_item_fee_mock();
        $wc_order = (new WC_Order_Fixture( $shipping_total, $shipping_tax_rate ))->get_wc_order_mock();

        // Consecutive calls for WC_Order->get_items()
        $wc_order->method( 'get_items' )->withConsecutive( array('line_item'),  array('shipping'), array('fee') )
                       ->willReturnOnConsecutiveCalls( array( $wc_order_item_product ), array($wc_order_item_shipping), array($wc_order_item_fee) );


        $shopping_cart_service = new ShoppingCartService();
        $shopping_cart = $shopping_cart_service->create_shopping_cart($wc_order, 'EUR');
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
    public function test_create_shopping_cart_has_values_test_case_1() {
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
        $wc_order_item_product = (new WC_Order_Item_Product_Fixture( $product_id, $product_name, $product_price, $product_quantity, $product_tax_rate, $discount_percentage, sanitize_title('Tax Class Name')))->get_wc_order_item_product_mock();
        $wc_order_item_shipping = (new WC_Order_Item_Shipping_Fixture( $shipping_total, $shipping_tax_rate ))->get_wc_order_item_shipping_mock();
        $wc_order_item_fee = (new WC_Order_Item_Fee_Fixture( 3.30, 21 ))->get_wc_order_item_fee_mock();
        $wc_order = (new WC_Order_Fixture( $shipping_total, $shipping_tax_rate ))->get_wc_order_mock();

        // Consecutive calls for WC_Order->get_items()
        $wc_order->method( 'get_items' )->withConsecutive( array('line_item'),  array('shipping'), array('fee') )
                 ->willReturnOnConsecutiveCalls( array( $wc_order_item_product ), array($wc_order_item_shipping), array($wc_order_item_fee) );


        $shopping_cart_service = new ShoppingCartService();
        $shopping_cart = $shopping_cart_service->create_shopping_cart($wc_order, 'EUR');
        $output = $shopping_cart->getData();

        $product_item       = $output['items'][0];
        $product_shipping   = $output['items'][1];
        $product_fee        = $output['items'][2];

        $this->assertEquals( 'Vneck Tshirt - Coupon applied: - 3.60 EUR', $product_item['name'] );
        $this->assertEquals( '', $product_item['description'] );
        $this->assertEquals( '16.2000000000', $product_item['unit_price'] );
        $this->assertEquals( 'EUR', $product_item['currency'] );
        $this->assertEquals( '2', $product_item['quantity'] );
        $this->assertEquals( '11', $product_item['merchant_item_id'] );
        $this->assertEquals( '21', $product_item['tax_table_selector'] );
        $this->assertEquals( '', $product_item['weight']['unit'] );
        $this->assertEquals( '', $product_item['weight']['value'] );

        $this->assertEquals( 'Shipping', $product_shipping['name'] );
        $this->assertEquals( '', $product_shipping['description'] );
        $this->assertEquals( '4.9900000000', $product_shipping['unit_price'] );
        $this->assertEquals( 'EUR', $product_shipping['currency'] );
        $this->assertEquals( '1', $product_shipping['quantity'] );
        $this->assertEquals( ShippingItem::MULTISAFEPAY_SHIPPING_ITEM_CODE, $product_shipping['merchant_item_id'] );
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

    /**
     * @covers \MultiSafepay\WooCommerce\Services\ShoppingCartService::create_shopping_cart
     */
    public function test_create_shopping_cart_has_values_test_case_2() {
        $product_id                 = 11;
        $product_name               = 'Vneck Tshirt';
        $product_price              = 18.00;
        $product_tax_rate           = 21;
        $product_quantity           = 2;
        $discount_percentage        = 10;
        $shipping_total             = 0;
        $shipping_tax_rate          = 0;

        // Set taxes.
        $tax_fixture = new TaxesFixture( 'Tax Rate Name', 21, 'Tax Class Name' );
        $tax_fixture->register_tax_rate();

        // Set Products.
        $wc_order_item_product = (new WC_Order_Item_Product_Fixture( $product_id, $product_name, $product_price, $product_quantity, $product_tax_rate, $discount_percentage, sanitize_title('Tax Class Name')))->get_wc_order_item_product_mock();
        $wc_order_item_fee = (new WC_Order_Item_Fee_Fixture( 3.30, 21 ))->get_wc_order_item_fee_mock();
        $wc_order = (new WC_Order_Fixture( $shipping_total, $shipping_tax_rate ))->get_wc_order_mock();

        // Consecutive calls for WC_Order->get_items()
        $wc_order->method( 'get_items' )->withConsecutive( array('line_item'), array('shipping'), array('fee') )
                 ->willReturnOnConsecutiveCalls( array( $wc_order_item_product ), array(), array($wc_order_item_fee) );


        $shopping_cart_service = new ShoppingCartService();
        $shopping_cart = $shopping_cart_service->create_shopping_cart($wc_order, 'EUR');
        $output = $shopping_cart->getData();

        $product_item       = $output['items'][0];
        $product_fee        = $output['items'][1];

        $this->assertEquals( 'Vneck Tshirt - Coupon applied: - 3.60 EUR', $product_item['name'] );
        $this->assertEquals( '', $product_item['description'] );
        $this->assertEquals( '16.2000000000', $product_item['unit_price'] );
        $this->assertEquals( 'EUR', $product_item['currency'] );
        $this->assertEquals( '2', $product_item['quantity'] );
        $this->assertEquals( '11', $product_item['merchant_item_id'] );
        $this->assertEquals( '21', $product_item['tax_table_selector'] );
        $this->assertEquals( '', $product_item['weight']['unit'] );
        $this->assertEquals( '', $product_item['weight']['value'] );


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

    /**
     * @covers \MultiSafepay\WooCommerce\Services\ShoppingCartService::create_shopping_cart
     */
    public function test_create_shopping_cart_has_values_test_case_3() {
        $product_1_id                 = 11;
        $product_1_name               = 'Product 1';
        $product_1_price              = 18.00;
        $product_1_tax_rate           = 21;
        $product_1_quantity           = 2;

        $product_2_id                 = 21;
        $product_2_name               = 'Product 2';
        $product_2_price              = 16.00;
        $product_2_tax_rate           = 10;
        $product_2_quantity           = 1;

        $discount_percentage        = 10;

        $shipping_total             = 4.99;
        $shipping_tax_rate          = 21;

        // Set taxes. 21% and 10%
        $tax_fixture = new TaxesFixture( 'Tax Rate Name 21', 21, 'Tax Class Name 21' );
        $tax_fixture->register_tax_rate();
        $tax_fixture_2 = new TaxesFixture( 'Tax Rate Name 10', 10, 'Tax Class Name 10' );
        $tax_fixture_2->register_tax_rate();

        // Set Products.
        $items = array();

        $product_1 = (new WC_Order_Item_Product_Fixture( $product_1_id, $product_1_name, $product_1_price, $product_1_quantity, $product_1_tax_rate, 0, sanitize_title('Tax Class Name 21')))->get_wc_order_item_product_mock();
        $items[] = $product_1;

        $product_2 = (new WC_Order_Item_Product_Fixture( $product_2_id, $product_2_name, $product_2_price, $product_2_quantity, $product_2_tax_rate, $discount_percentage, sanitize_title('Tax Class Name 10')))->get_wc_order_item_product_mock();
        $items[] = $product_2;

        $wc_order_item_shipping = (new WC_Order_Item_Shipping_Fixture( $shipping_total, $shipping_tax_rate ))->get_wc_order_item_shipping_mock();
        $wc_order_item_fee = (new WC_Order_Item_Fee_Fixture( 3.30, 21 ))->get_wc_order_item_fee_mock();
        $wc_order = (new WC_Order_Fixture( $shipping_total, $shipping_tax_rate ))->get_wc_order_mock();

        // Consecutive calls for WC_Order->get_items()
        $wc_order->method( 'get_items' )->withConsecutive( array('line_item'), array('shipping'), array('fee') )
                 ->willReturnOnConsecutiveCalls( $items, array( $wc_order_item_shipping ), array($wc_order_item_fee) );


        $shopping_cart_service = new ShoppingCartService();
        $shopping_cart = $shopping_cart_service->create_shopping_cart($wc_order, 'EUR');
        $output = $shopping_cart->getData();
        $product_item_1       = $output['items'][0];
        $product_item_2       = $output['items'][1];
        $product_shipping     = $output['items'][2];
        $product_fee          = $output['items'][3];

        $this->assertEquals( 'Product 1', $product_item_1['name'] );
        $this->assertEquals( '', $product_item_1['description'] );
        $this->assertEquals( '18.0000000000', $product_item_1['unit_price'] );
        $this->assertEquals( 'EUR', $product_item_1['currency'] );
        $this->assertEquals( '2', $product_item_1['quantity'] );
        $this->assertEquals( '11', $product_item_1['merchant_item_id'] );
        $this->assertEquals( '21', $product_item_1['tax_table_selector'] );
        $this->assertEquals( '', $product_item_1['weight']['unit'] );
        $this->assertEquals( '', $product_item_1['weight']['value'] );

        $this->assertEquals( 'Product 2 - Coupon applied: - 1.60 EUR', $product_item_2['name'] );
        $this->assertEquals( '', $product_item_2['description'] );
        $this->assertEquals( '14.4000000000', $product_item_2['unit_price'] );
        $this->assertEquals( 'EUR', $product_item_2['currency'] );
        $this->assertEquals( '1', $product_item_2['quantity'] );
        $this->assertEquals( '21', $product_item_2['merchant_item_id'] );
        $this->assertEquals( '10', $product_item_2['tax_table_selector'] );
        $this->assertEquals( '', $product_item_2['weight']['unit'] );
        $this->assertEquals( '', $product_item_2['weight']['value'] );

        $this->assertEquals( 'Shipping', $product_shipping['name'] );
        $this->assertEquals( '', $product_shipping['description'] );
        $this->assertEquals( '4.9900000000', $product_shipping['unit_price'] );
        $this->assertEquals( 'EUR', $product_shipping['currency'] );
        $this->assertEquals( '1', $product_shipping['quantity'] );
        $this->assertEquals( ShippingItem::MULTISAFEPAY_SHIPPING_ITEM_CODE, $product_shipping['merchant_item_id'] );
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

    /**
     * @covers \MultiSafepay\WooCommerce\Services\ShoppingCartService::create_shopping_cart
     */
    public function test_create_shopping_cart_has_values_test_case_4() {
        $product_id                 = 11;
        $product_name               = 'Vneck Tshirt';
        $product_price              = 18.00;
        $product_tax_rate           = 0;
        $product_quantity           = 2;
        $discount_percentage        = 10;
        $shipping_total             = 0;
        $shipping_tax_rate          = 0;

        // Set Products.
        $wc_order_item_product = (new WC_Order_Item_Product_Fixture( $product_id, $product_name, $product_price, $product_quantity, $product_tax_rate, $discount_percentage, sanitize_title('Tax Class Name')))->get_wc_order_item_product_mock();
        $wc_order_item_fee = (new WC_Order_Item_Fee_Fixture( 0.00, 0 ))->get_wc_order_item_fee_mock();
        $wc_order = (new WC_Order_Fixture( $shipping_total, $shipping_tax_rate ))->get_wc_order_mock();

        // Consecutive calls for WC_Order->get_items()
        $wc_order->method( 'get_items' )->withConsecutive( array('line_item'), array('shipping'), array('fee') )
                 ->willReturnOnConsecutiveCalls( array( $wc_order_item_product ), array(), array($wc_order_item_fee) );


        $shopping_cart_service = new ShoppingCartService();
        $shopping_cart = $shopping_cart_service->create_shopping_cart($wc_order, 'EUR');
        $output = $shopping_cart->getData();

        $product_item       = $output['items'][0];
        $product_fee        = $output['items'][1];

        $this->assertEquals( 'Vneck Tshirt - Coupon applied: - 3.60 EUR', $product_item['name'] );
        $this->assertEquals( '', $product_item['description'] );
        $this->assertEquals( '16.2000000000', $product_item['unit_price'] );
        $this->assertEquals( 'EUR', $product_item['currency'] );
        $this->assertEquals( '2', $product_item['quantity'] );
        $this->assertEquals( '11', $product_item['merchant_item_id'] );
        $this->assertEquals( '0', $product_item['tax_table_selector'] );
        $this->assertEquals( '', $product_item['weight']['unit'] );
        $this->assertEquals( '', $product_item['weight']['value'] );


        $this->assertEquals( 'Fee Name', $product_fee['name'] );
        $this->assertEquals( '', $product_fee['description'] );
        $this->assertEquals( '0.00', $product_fee['unit_price'] );
        $this->assertEquals( 'EUR', $product_fee['currency'] );
        $this->assertEquals( '1', $product_fee['quantity'] );
        $this->assertEquals( '17', $product_fee['merchant_item_id'] );
        $this->assertEquals( '0', $product_fee['tax_table_selector'] );
        $this->assertEquals( '', $product_fee['weight']['unit'] );
        $this->assertEquals( '', $product_fee['weight']['value'] );
    }

    public function tearDown() {
        TaxesFixture::delete_tax_classes();
    }

}
