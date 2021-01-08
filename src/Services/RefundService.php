<?php declare( strict_types=1 );

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

namespace MultiSafepay\WooCommerce\Services;

use WC_Order;
use WC_Order_Refund;

/**
 * Class ShoppingCartService
 *
 * @package MultiSafepay\WooCommerce\Services
 */
class RefundService {

    const NOT_ALLOW_REFUND_ORDER_STATUSES = array(
        'pending',
        'on-hold',
        'failed',
    );

    /**
     * @param WC_Order $order
     *
     * @return WC_Order_Refund
     */
    public function get_latest_refund( WC_Order $order ) {
        $refunds = $order->get_refunds();
        usort(
            $refunds,
            function ( WC_Order_Refund $refund_one, WC_Order_Refund $refund_two ) {
                return $refund_two->get_date_created()->getTimestamp() - $refund_one->get_date_created()->getTimestamp();
            }
        );

        return $refunds[0];
    }

    /**
     * @param WC_Order_Refund $refund
     *
     * @return array
     */
    public function get_refund_items_and_quantity( WC_Order_Refund $refund ) {
        $items = array();
        foreach ( $refund->get_items() as $item ) {
            $merchant_item_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();

            $items[ $merchant_item_id ] = abs( $item->get_quantity() );
        }

        return $items;
    }

}
