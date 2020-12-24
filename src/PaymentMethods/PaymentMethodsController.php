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
 */

namespace MultiSafepay\WooCommerce\PaymentMethods;

use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\WooCommerce\Services\SdkService;
use WC_Order;

/**
 * The payment methods controller.
 *
 * Defines all the functionalities needed to register the Payment Methods actions and filters
 *
 * @since   4.0.0
 */
class PaymentMethodsController {

    /**
     * The ID of this plugin.
     *
     * @var      string    The ID of this plugin.
     */
	private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @var      string    The current version of this plugin.
     */
	private $version;

    /**
     * The plugin dir url
     *
     * @var      string    The plugin directory url
     */
    private $plugin_dir_url;

    /**
     * Initialize the class and set its properties.
     *
     * @param      string $plugin_name       The name of this plugin.
     * @param      string $version           The version of this plugin.
     * @param      string $plugin_dir_url    The plugin dir url of this plugin.
     */
	public function __construct( string $plugin_name, string $version, string $plugin_dir_url ) {
		$this->plugin_name    = $plugin_name;
		$this->version        = $version;
		$this->plugin_dir_url = $plugin_dir_url;
	}

	/**
	 * Register the stylesheets related with the payment methods
	 *
     * @see https://developer.wordpress.org/reference/functions/wp_enqueue_style/
     *
     * @return void
	 */
	public function enqueue_styles(): void {
		wp_enqueue_style( $this->plugin_name, $this->plugin_dir_url . 'assets/public/css/multisafepay-public.css', array(), $this->version, 'all' );
	}

    /**
     * Merge existing gateways and MultiSafepay Gateways
     *
     * @param array $gateways
     * @return array
     */
    public static function get_gateways( array $gateways ): array {
        return array_merge( $gateways, Gateways::GATEWAYS );
    }

    /**
     * Filter the payment methods by the countries defined in their settings
     *
     * @param   array $payment_gateways
     * @return  array
     */
    public function filter_gateway_per_country( array $payment_gateways ): array {
        $customer_country = ( WC()->customer ) ? WC()->customer->get_billing_country() : false;
        foreach ( $payment_gateways as $gateway_id => $gateway ) {
            if ( ! empty( $gateway->countries ) && $customer_country && ! in_array( $customer_country, $gateway->countries, true ) ) {
                unset( $payment_gateways[ $gateway_id ] );
            }
        }
        return $payment_gateways;
    }

    /**
     * Filter the payment methods by min amount defined in their settings
     *
     * @param   array $payment_gateways
     * @return  array
     */
    public function filter_gateway_per_min_amount( array $payment_gateways ): array {
        $total_amount = ( WC()->cart ) ? WC()->cart->total : false;
        foreach ( $payment_gateways as $gateway_id => $gateway ) {
            if ( ! empty( $gateway->min_amount ) && $total_amount < $gateway->min_amount ) {
                unset( $payment_gateways[ $gateway_id ] );
            }
        }
        return $payment_gateways;
    }

    /**
     * Set the MultiSafepay transaction as shipped when the order
     * status change to the one defined as shipped in the settings.
     *
     * @param   int $order_id
     * @return  void
     */
    public function set_msp_transaction_as_shipped( int $order_id ): void {
        $sdk                 = new SdkService();
        $transaction_manager = $sdk->get_transaction_manager();
        $update_order        = new UpdateRequest();
        $update_order->addId( (string) $order_id );
        $update_order->addStatus( 'shipped' );
        $transaction_manager->update( (string) $order_id, $update_order );
    }



    /**
     * Set the MultiSafepay transaction as invoiced when the order
     * status change to the one defined as invoiced in the settings.
     *
     * @param   int $order_id
     * @return  void
     */
    public function set_msp_transaction_as_invoiced( int $order_id ): void {
        $sdk                 = new SdkService();
        $transaction_manager = $sdk->get_transaction_manager();
        $update_order        = new UpdateRequest();
        $update_order->addData( array( 'invoice_id' => $order_id ) );
        $transaction_manager->update( (string) $order_id, $update_order );
    }

}
