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

namespace MultiSafepay\WooCommerce\PaymentMethods;

use MultiSafepay\WooCommerce\PaymentMethods\MultiSafepay;

/**
 * The payment methods controller.
 *
 * Defines all the functionalities needed to register the Payment Methods actions and filters
 *
 * @since   4.0.0
 */
class PaymentMethodsController {

    const GATEWAYS = [MultiSafepay::class, Ideal::class];

    /**
     * The ID of this plugin.
     *
     * @var      string    $plugin_name    The ID of this plugin.
     */
	private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @var      string    $version    The current version of this plugin.
     */
	private $version;

    /**
     * The plugin dir url
     *
     * @var      string    $plugin_dir_url    The plugin directory url
     */
    private $plugin_dir_url;

    /**
     * Initialize the class and set its properties.
     *
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
	public function __construct( $plugin_name, $version, $plugin_dir_url ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin_dir_url = $plugin_dir_url;
	}

	/**
	 * Register the stylesheets related with the payment methods
	 *
     * @see https://developer.wordpress.org/reference/functions/wp_enqueue_style/
     * @todo restrict this to checkout page. Probably won`` be needed in any other place.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, $this->plugin_dir_url . 'assets/public/css/multisafepay-public.css', array(), $this->version, 'all' );
	}

    /**
     * Register the scripts for the payment methods
     *
     * @see https://developer.wordpress.org/reference/functions/wp_enqueue_script/
     * @todo restrict this to checkout page. Probably won`` be needed in any other place.
     */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, $this->plugin_dir_url . 'assets/public/js/multisafepay-public.js', array( 'jquery' ), $this->version, false );
	}

    /**
     * Merge existing gateways and MultiSafepay Gateways
     *
     * @param array $gateways
     * @return array
     */
    public static function get_gateways( array $gateways ): array {
        return array_merge($gateways, self::GATEWAYS);
    }

    /**
     * Initialize all MultiSafepay payment method instances with all specific settings for that gateway
     *
     * @return void
     */
    public function init_multisafepay_payment_methods(): void {
        foreach (self::GATEWAYS as $gateway) {
            new $gateway();
        }
    }

}
