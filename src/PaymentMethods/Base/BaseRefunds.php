<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use Exception;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\RefundRequest;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\ValueObject\CartItem;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Utils\MoneyUtil;
use Psr\Http\Client\ClientExceptionInterface;
use WC_Order;
use WP_Error;

trait BaseRefunds {

    /**
     * Process the refund.
     *
     * @param integer $order_id Order ID.
     * @param float   $amount Amount to be refunded.
     * @param string  $reason Reason description.
     *
     * @return  mixed boolean|WP_Error
     *
     * @throws ClientExceptionInterface
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {

        // Refund amount can not be 0
        if ( 0.00 === (float) $amount ) {
            return new WP_Error( '400', __( 'Amount of refund should be higher than 0', 'multisafepay' ) );
        }

        /** @var SdkService $sdk */
        $sdk = new SdkService();

        /** @var TransactionManager $transaction_manager */
        $transaction_manager = $sdk->get_transaction_manager();

        /** @var WC_Order $order */
        $order = wc_get_order( $order_id );

        /** @var TransactionResponse $multisafepay_transaction */
        $multisafepay_transaction = $transaction_manager->get( $order->get_order_number() );

        /** @var RefundRequest $refund_request */
        $refund_request = $transaction_manager->createRefundRequest( $multisafepay_transaction );

        $refund_request->addDescriptionText( $reason );

        if ( $multisafepay_transaction->requiresShoppingCart() ) {
            $refunds                 = $order->get_refunds();
            $refund_merchant_item_id = reset( $refunds )->id;

            $cart_item = new CartItem();
            $cart_item->addName( __( 'Refund', 'multisafepay' ) )
                ->addQuantity( 1 )
                ->addUnitPrice( MoneyUtil::create_money( (float) $amount, $order->get_currency() )->negative() )
                ->addMerchantItemId( 'refund_id_' . $refund_merchant_item_id )
                ->addTaxRate( 0 );

            $refund_request->getCheckoutData()->addItem( $cart_item );
        }

        if ( ! $multisafepay_transaction->requiresShoppingCart() ) {
            $refund_request->addMoney( MoneyUtil::create_money( (float) $amount, $order->get_currency() ) );
        }

        try {
            $error = null;
            $transaction_manager->refund( $multisafepay_transaction, $refund_request );
        } catch ( Exception | ClientExceptionInterface | ApiException $exception ) {
            $error = __( 'Error:', 'multisafepay' ) . htmlspecialchars( $exception->getMessage() );
            $this->logger->log_error( $error );
            wc_add_notice( $error, 'error' );
        }

        if ( ! $error ) {
            /* translators: %1$: The currency code. %2$ The transaction amount */
            $note = sprintf( __( 'Refund of %1$s%2$s has been processed successfully.', 'multisafepay' ), get_woocommerce_currency_symbol( $order->get_currency() ), $amount );
            $this->logger->log_info( $note );
            $order->add_order_note( $note );
            return true;
        }

        if ( get_option( 'multisafepay_debugmode', false ) ) {
            /* translators: %1$: The order ID. %2$ The PSP transaction ID */
            $message = sprintf( __( 'Refund for Order ID: %1$s with transactionId: %2$s gives message: %3$s.', 'multisafepay' ), $order_id, $multisafepay_transaction->getTransactionId(), $error );
            $this->logger->log_warning( $message );
        }

        return false;
    }

}
