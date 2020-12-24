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

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\QrCode;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;

class IdealQr extends BasePaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_idealqr';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'IDEALQR';
    }

    /**
     * @return string
     */
    public function get_payment_method_type(): string {
        return 'redirect';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return __( 'iDEAL QR', 'multisafepay' );
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
            __( 'Easily receive payments with a simple scan of an iDEAL QR code. <br />Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay' ),
            'https://docs.multisafepay.com/payment-methods/banks/idealqr/?utm_source=woocommerce&utm_medium=woocommerce-cms&utm_campaign=woocommerce-cms',
            $this->get_payment_method_title()
        );
        return $method_description;
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'ideal-qr.png';
    }

    /**
     * @param array|null $data
     * @return GatewayInfoInterface
     */
    public function get_gateway_info( array $data = null ): GatewayInfoInterface {
        $order        = wc_get_order( $data['order_id'] );
        $gateway_info = new QrCode();
        return $gateway_info
            ->addMaxAmount( $order->get_amount() )
            ->addMinAmount( $order->get_amount() );
    }

}
