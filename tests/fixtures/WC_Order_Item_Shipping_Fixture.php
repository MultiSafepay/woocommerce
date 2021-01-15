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

    public function get_wc_order_item_shipping_mock() {
        $wc_order_item_shipping =  $this->getMockBuilder( 'WC_Order_Item_Shipping' )
                                        ->disableOriginalConstructor()
                                        ->setMethods( array( 'get_id', 'get_name', 'get_total', 'get_taxes' ) )
                                        ->getMock();
        $wc_order_item_shipping->method( 'get_id' )->willReturn( 99 );
        $wc_order_item_shipping->method( 'get_name' )->willReturn( 'Shipping' );
        $wc_order_item_shipping->method( 'get_total' )->willReturn( $this->shipping_total );
        $wc_order_item_shipping->method( 'get_taxes' )->willReturn( array('total' => array('4' => $this->shipping_total * ( $this->shipping_tax_rate / 100 ) ) ) );
        return $wc_order_item_shipping;

    }

}
