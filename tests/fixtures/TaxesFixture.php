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

use WC_Tax;

/**
 * Class TaxesFixture
 *
 * @package MultiSafepay\WooCommerce\Tests\Fixtures
 */
class TaxesFixture {

    /**
     * @var string
     */
    private $tax_rate_name;

    /**
     * @var float
     */
    private $tax_rate;

    /**
     * @var string
     */
    private $tax_class_name;

    /**
     * TaxesFixture constructor.
     *
     * @param string $tax_rate_name
     * @param float $tax_rate
     * @param string $tax_class_name
     */
    public function __construct(  string $tax_rate_name, float $tax_rate, string $tax_class_name ) {
        $this->tax_rate         = $tax_rate_name;
        $this->tax_rate         = $tax_rate;
        $this->tax_class_name   = $tax_class_name;
        add_filter('woocommerce_get_tax_location', function($location, $tax_class, $customer) { return array( 'NL', '', '1033 SC', 'Amsterdam' ); }, 10, 3);
    }

    /**
     * @param float $tax_rate
     * @param string $tax_class_name
     * @return void
     */
    public function register_tax_rate(): void {
        $this->validate_tax_class( $this->tax_class_name );
        $tax_rate_data = array(
            'tax_rate_country'  => '*',
            'tax_rate_state'    => '*',
            'tax_rate'          => $this->tax_rate,
            'tax_rate_name'     => $this->tax_rate_name,
            'tax_rate_priority' => 1,
            'tax_rate_compound' => 0,
            'tax_rate_shipping' => 1,
            'tax_rate_order'    => 0,
            'tax_rate_class'    => sanitize_title($this->tax_class_name)
        );
        $tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate_data );
    }

    private function validate_tax_class(): void {
        $tax_classes = WC_Tax::get_tax_classes();
        if( !in_array( $this->tax_class_name, $tax_classes, true ) ) {
            $this->insert_tax_class();
        }
    }

    /**
     * @param string $tax_class_name
     * @return void
     */
    private function insert_tax_class(): void {
        WC_Tax::create_tax_class( $this->tax_class_name, sanitize_title( $this->tax_class_name ) );
    }

    /**
     * @return void
     */
    public static function delete_tax_classes(): void {
        $tax_classes = WC_Tax::get_tax_classes();
        foreach ($tax_classes as $tax_class) {
            WC_Tax::delete_tax_class_by('name', $tax_class);
        }
    }

}
