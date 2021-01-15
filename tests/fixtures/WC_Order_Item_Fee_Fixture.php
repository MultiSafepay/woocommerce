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

    public function get_wc_order_item_fee_mock() {
        $wc_order_item_fee =  $this->getMockBuilder( 'WC_Order_Item_Fee' )
                                   ->disableOriginalConstructor()
                                   ->setMethods( array( 'get_id', 'get_name', 'get_type', 'get_quantity', 'get_total', 'get_total_tax', 'get_product_id', 'get_taxes' ) )
                                   ->getMock();
        $wc_order_item_fee->method( 'get_id' )->willReturn( 17 );
        $wc_order_item_fee->method( 'get_name' )->willReturn( 'Fee Name' );
        $wc_order_item_fee->method( 'get_type' )->willReturn( 'fee' );
        $wc_order_item_fee->method( 'get_quantity' )->willReturn( 1 );
        $wc_order_item_fee->method( 'get_total' )->willReturn( $this->fee_total );
        $wc_order_item_fee->method( 'get_total_tax' )->willReturn( $this->fee_total * ( $this->fee_tax_rate / 100 ) );
        $wc_order_item_fee->method( 'get_product_id' )->willReturn( 17 );
        $wc_order_item_fee->method( 'get_taxes' )->willReturn( array( 'total' => array('4' => $this->fee_total * ( $this->fee_tax_rate / 100 ) ) ) );
        return $wc_order_item_fee;
    }

}
