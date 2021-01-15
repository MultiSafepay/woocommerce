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

use WC_Product;
use WP_UnitTestCase;

/**
 * Class WC_ProductFixture
 *
 * @package MultiSafepay\WooCommerce\Tests\Fixtures
 */
class WC_Product_Fixture extends WP_UnitTestCase {

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
     * WC_ProductFixture constructor.
     *
     * @param float $product_price
     * @param int $product_quantity
     * @param float $product_tax_rate
     */
    public function __construct(  float $product_price, int $product_quantity, float $product_tax_rate ) {
        $this->product_price      = $product_price;
        $this->product_quantity   = $product_quantity;
        $this->product_tax_rate   = $product_tax_rate;
    }

    public function get_wc_product_mock() {
        $wc_product =  $this->getMockBuilder( 'WC_Product' )
                            ->disableOriginalConstructor()
                            ->setMethods( array( 'get_price', 'get_subtotal', 'get_total_tax' ) )
                            ->getMock();
        $wc_product->method( 'get_price' )->willReturn( $this->product_price );
        $wc_product->method( 'get_subtotal' )->willReturn( ( $this->product_price * $this->product_quantity) );
        $wc_product->method( 'get_total_tax' )->willReturn( $this->product_price * ($this->product_tax_rate / 100 ) );
        return $wc_product;
    }



}
