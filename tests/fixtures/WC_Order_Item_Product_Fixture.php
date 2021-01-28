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

namespace MultiSafepay\WooCommerce\Tests\Fixtures;

use WP_UnitTestCase;
use MultiSafepay\WooCommerce\Tests\Fixtures\WC_ProductFixture;

/**
 * Class WC_ProductFixture
 *
 * @package MultiSafepay\WooCommerce\Tests\Fixtures
 */
class WC_Order_Item_Product_Fixture extends WP_UnitTestCase {

    /**
     * @var int
     */
    private $product_id;

    /**
     * @var string
     */
    private $product_name;

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
     * @var int
     */
    private $discount_percentage;

    /**
     * @var string
     */
    private $tax_class_name;


    /**
     * WC_ProductFixture constructor.
     *
     * @param int $product_id
     * @param string $product_name
     * @param float $product_price
     * @param int $product_quantity
     * @param float $product_tax_rate
     * @param int $discount_percentage
     * @param string $tax_class_name
     */
    public function __construct(int $product_id, string $product_name,  float $product_price, int $product_quantity, float $product_tax_rate, int $discount_percentage, string $tax_class_name) {
        $this->product_id          = $product_id;
        $this->product_name        = $product_name;
        $this->product_price       = $product_price;
        $this->product_quantity    = $product_quantity;
        $this->product_tax_rate    = $product_tax_rate;
        $this->discount_percentage = $discount_percentage;
        $this->tax_class_name      = $tax_class_name;
    }

    public function get_wc_order_item_product_mock() {
        $product_subtotal = ( $this->product_price * ($this->product_tax_rate / 100 ) ) * $this->product_quantity;
        $product_discount = $this->product_price - ($this->product_price * ($this->discount_percentage / 100 ) );
        $product_total    = $product_discount * ($this->product_tax_rate / 100 ) * $this->product_quantity;

        $wc_order_item_product =  $this->getMockBuilder( 'WC_Order_Item_Product' )
                                       ->disableOriginalConstructor()
                                       ->setMethods( array( 'get_id', 'get_name', 'get_type', 'get_quantity', 'get_subtotal', 'get_total', 'get_total_tax', 'get_product_id', 'get_variation_id', 'get_tax_class', 'get_taxes', 'get_product' ) )
                                       ->getMock();
        $wc_order_item_product->method( 'get_id' )->willReturn( $this->product_id );
        $wc_order_item_product->method( 'get_name' )->willReturn( $this->product_name );
        $wc_order_item_product->method( 'get_type' )->willReturn( 'line_item' );
        $wc_order_item_product->method( 'get_quantity' )->willReturn( $this->product_quantity );
        $wc_order_item_product->method( 'get_subtotal' )->willReturn( ( $this->product_price * $this->product_quantity ) ) ;
        $wc_order_item_product->method( 'get_total' )->willReturn( ( ($this->product_price * $this->product_quantity) - ($this->product_price * $this->product_quantity * ($this->discount_percentage  / 100)) ) ) ;
        $wc_order_item_product->method( 'get_total_tax' )->will( $this->returnValue( $this->product_price * ($this->product_tax_rate / 100 ) ) );
        $wc_order_item_product->method( 'get_product_id' )->willReturn( $this->product_id );
        $wc_order_item_product->method( 'get_variation_id' )->willReturn( 0 );
        $wc_order_item_product->method( 'get_tax_class' )->willReturn( $this->tax_class_name );
        $wc_order_item_product->method( 'get_taxes' )->willReturn( array('total' => array('4' => $product_total), 'subtotal' => array('4' => $product_subtotal ) ) );
        $wc_order_item_product->method( 'get_product' )->willReturn( (new WC_Product_Fixture( $this->product_price, $this->product_quantity, $this->product_tax_rate))->get_wc_product_mock() );
        return $wc_order_item_product;
    }


}
