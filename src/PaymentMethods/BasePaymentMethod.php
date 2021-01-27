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

namespace MultiSafepay\WooCommerce\PaymentMethods;

use Exception;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\ValueObject\IbanNumber;
use MultiSafepay\WooCommerce\Services\OrderService;
use MultiSafepay\WooCommerce\Services\RefundService;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Utils\MoneyUtil;
use Psr\Http\Client\ClientExceptionInterface;
use WC_Countries;
use WC_Payment_Gateway;
use MultiSafepay\Exception\InvalidArgumentException;

abstract class BasePaymentMethod extends WC_Payment_Gateway implements PaymentMethodInterface {

    /**
     * What type of transaction, should be 'direct' or 'redirect'
     *
     * @var string
     */
    protected $type;

    /**
     * The MultiSafepay gateway code.
     *
     * @var string
     */
    protected $gateway_code;

    /**
     * An array with the keys of the required custom fields
     *
     * @var array
     */
    protected $checkout_fields_ids;

    /**
     * The minimun amount for the payment method
     *
     * @var string
     */
    protected $min_amount;

    /**
     * A custom initialized order status for this payment method
     *
     * @var string
     */
    protected $initial_order_status;

    /**
     * The plugin directory path
     *
     * @var string
     */
    protected $plugin_dir_path;

    /**
     * Construct for Core class.
     */
    public function __construct() {
        $this->supports            = array( 'products', 'refunds' );
        $this->id                  = $this->get_payment_method_id();
        $this->type                = $this->get_payment_method_type();
        $this->method_title        = $this->get_payment_method_title();
        $this->method_description  = $this->get_payment_method_description();
        $this->gateway_code        = $this->get_payment_method_code();
        $this->has_fields          = $this->has_fields();
        $this->checkout_fields_ids = $this->get_checkout_fields_ids();
        $this->icon                = $this->get_logo();
        $this->add_form_fields();
        $this->init_form_fields();
        $this->init_settings();

        $this->enabled              = $this->get_option( 'enabled', 'no' );
        $this->title                = $this->get_option( 'title', $this->get_method_title() );
        $this->description          = $this->get_option( 'description' );
        $this->max_amount           = $this->get_option( 'max_amount' );
        $this->min_amount           = $this->get_option( 'min_amount' );
        $this->countries            = $this->get_option( 'countries' );
        $this->initial_order_status = $this->get_option( 'initial_order_status', false );
        $this->plugin_dir_path      = plugin_dir_path( dirname( __DIR__ ) );
        $this->errors               = array();

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array(
                $this,
                'process_admin_options',
            )
        );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'display_errors' ) );
        add_action( 'woocommerce_api_' . $this->id, array( $this, 'callback' ) );
    }

    /**
     * Return the full path of the (locale) logo
     *
     * @return string
     */
    private function get_logo(): string {
        $language = substr( get_locale(), 0, 2 );

        $icon = $this->get_payment_method_icon();

        $icon_locale = substr_replace( $icon, "-$language", - 4, - 4 );
        if ( file_exists( WP_PLUGIN_DIR . '/multisafepay/assets/public/img/' . $icon_locale ) ) {
            $icon = $icon_locale;
        }

        return esc_url( plugins_url( '/assets/public/img/' . $icon, dirname( __DIR__ ) ) );
    }

    /**
     * Return an array of allowed countries defined in WooCommerce Settings.
     *
     * @return array
     */
    private function get_countries(): array {
        $countries         = new WC_Countries();
        $allowed_countries = $countries->get_allowed_countries();

        return $allowed_countries;
    }

    /**
     * Return if payment methods requires custom checkout fields
     *
     * @return boolean
     */
    public function has_fields(): bool {
        return false;
    }

    /**
     * Return the custom checkout fields id`s
     *
     * @return array
     */
    public function get_checkout_fields_ids(): array {
        return array();
    }

    /**
     * Return the gateway info
     *
     * @param array|null $data
     *
     * @return GatewayInfoInterface
     */
    public function get_gateway_info( array $data = null ): GatewayInfoInterface {
        return new BaseGatewayInfo();
    }

    /**
     * Define the form option - settings fields.
     *
     * @return  void
     */
    public function add_form_fields(): void {
        $this->form_fields = array(
            'enabled'              => array(
                'title'   => __( 'Enable/Disable', 'multisafepay' ),
                'label'   => 'Enable ' . $this->get_method_title() . ' Gateway',
                'type'    => 'checkbox',
                'default' => 'no',
            ),
            'title'                => array(
                'title'    => __( 'Title', 'multisafepay' ),
                'type'     => 'text',
                'desc_tip' => __( 'This controls the title which the user sees during checkout.', 'multisafepay' ),
                'default'  => $this->get_method_title(),
            ),
            'description'          => array(
                'title'    => __( 'Description', 'multisafepay' ),
                'type'     => 'textarea',
                'desc_tip' => __( 'This controls the description which the user sees during checkout.', 'multisafepay' ),
                'default'  => '',
            ),
            'initial_order_status' => array(
                'title'    => __( 'Initial Order Status', 'multisafepay' ),
                'type'     => 'select',
                'options'  => $this->get_order_statuses(),
                'desc_tip' => __( 'Initial order status for this payment method.', 'multisafepay' ),
                'default'  => 'wc-default',
            ),
            'min_amount'           => array(
                'title'    => __( 'Min Amount', 'multisafepay' ),
                'type'     => 'decimal',
                'desc_tip' => __( 'This payment method is not shown in the checkout if the order total is lower than the defined amount. Leave blank for no restrictions.', 'multisafepay' ),
                'default'  => $this->get_option( 'min_amount', '' ),
            ),
            'max_amount'           => array(
                'title'    => __( 'Max Amount', 'multisafepay' ),
                'type'     => 'decimal',
                'desc_tip' => __( 'This payment method is not shown in the checkout if the order total exceeds a certain amount. Leave blank for no restrictions.', 'multisafepay' ),
                'default'  => $this->get_option( 'max_amount', '' ),
            ),
            'countries'            => array(
                'title'       => __( 'Country', 'multisafepay' ),
                'type'        => 'multiselect',
                'description' => __( 'If you select one or more countries, this payment method won\'t show in the checkout page, if the payment address`s country of the customer match with the selected values. Leave blank for no restrictions.', 'multisafepay' ),
                'desc_tip'    => __( 'For most operating system and configurations, you must hold Ctrl or Cmd in your keyboard, while you click in the options to select more than one value.', 'multisafepay' ),
                'options'     => $this->get_countries(),
                'default'     => $this->get_option( 'countries', array() ),
            ),
        );
    }

    /**
     * Process the payment and return the result.
     *
     * @param integer $order_id Order ID.
     *
     * @return  array|mixed|void
     */
    public function process_payment( $order_id ): array {
        $sdk                 = new SdkService();
        $transaction_manager = $sdk->get_transaction_manager();
        $order_service       = new OrderService();
        $order_request       = $order_service->create_order_request( $order_id, $this->gateway_code, $this->type, $this->id, $this->get_gateway_info() );
        $transaction         = $transaction_manager->create( $order_request );

        $order = wc_get_order( $order_id );

        if ( $this->initial_order_status && 'wc-default' !== $this->initial_order_status && $transaction->getPaymentUrl() ) {
            $order->update_status( str_replace( 'wc-', '', $this->initial_order_status ), __( 'Transaction has been initialized.', 'multisafepay' ) );
        }

        if ( ( ! $this->initial_order_status || 'wc-default' === $this->initial_order_status ) && $transaction->getPaymentUrl() ) {
            $order->update_status( str_replace( 'wc-', '', get_option( 'multisafepay_initialized_status', 'wc-pending' ) ), __( 'Transaction has been initialized.', 'multisafepay' ) );
        }

        return array(
            'result'   => 'success',
            'redirect' => esc_url_raw( $transaction->getPaymentUrl() ),
        );
    }

    /**
     * Process the refund.
     *
     * @param integer $order_id Order ID.
     * @param float   $amount     Amount to be refunded.
     * @param string  $reason    Reason description.
     *
     * @return  boolean
     * @throws  Exception
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ): bool {

        if ( 0.00 === (float) $amount ) {
            throw new Exception( __( 'Amount of refund should be higher than 0', 'multisafepay' ) );
        }

        $sdk                 = new SdkService();
        $transaction_manager = $sdk->get_transaction_manager();

        $order           = wc_get_order( $order_id );
        $msp_transaction = $transaction_manager->get( $order->get_order_number() );

        $refund_request = $transaction_manager->createRefundRequest( $msp_transaction );
        $refund_request->addDescriptionText( $reason );

        // If the used gateway is a billing suite gateway, create the refund based on items
        if ( in_array( $msp_transaction->getPaymentDetails()->getType(), Gateways::GATEWAYS_WITH_SHOPPING_CART, true ) ) {
            $refund_service = new RefundService();

            $refund       = $refund_service->get_latest_refund( $order );
            $refund_items = $refund_service->get_refund_items_and_quantity( $refund );
            foreach ( $refund_items as $item_id => $quantity ) {
                $refund_request->getCheckoutData()->refundByMerchantItemId( (string) $item_id, $quantity );
            }

            if ( $amount !== $order->get_total() ) {
                throw new Exception( __( 'Partial refund is not possible with billing suite payment methods', 'multisafepay' ) );
            }
        }

        if ( ! in_array( $msp_transaction->getPaymentDetails()->getType(), Gateways::GATEWAYS_WITH_SHOPPING_CART, true ) ) {
            $refund_request->addMoney( MoneyUtil::create_money( (float) $amount, $order->get_currency() ) );
        }

        try {
            $msg = null;
            $transaction_manager->refund( $msp_transaction, $refund_request );
        } catch ( \Exception $exception ) {
            $msg = __( 'Error:', 'multisafepay' ) . htmlspecialchars( $exception->getMessage() );
            wc_add_notice( $msg, 'error' );
        }

        if ( ! $msg ) {
            /* translators: %1$: The currency code. %2$ The transaction amount */
            $order->add_order_note( sprintf( __( 'Refund of %1$s%2$s has been processed successfully.', 'multisafepay' ), get_woocommerce_currency_symbol( $order->get_currency() ), $amount ) );

            return true;
        }

        if ( get_option( 'multisafepay_debugmode', false ) ) {
            $logger = wc_get_logger();
            /* translators: %1$: The order ID. %2$ The PSP transaction ID */
            $message = sprintf( __( 'Refund for Order ID: %1$s with transactionId: %2$s gives message: %3$s.', 'multisafepay' ), $order_id, $msp_transaction->getTransactionId(), $msg );
            $logger->log( 'info', $message );
        }

        return false;
    }


    /**
     * Process the callback of the transaction.
     *
     * @return  void
     */
    public function callback(): void {
        $required_args = array( 'transactionid', 'timestamp' );
        foreach ( $required_args as $arg ) {
            if ( ! isset( $_GET[ $arg ] ) || empty( $_GET[ $arg ] ) ) {
                wp_die( esc_html__( 'Invalid request', 'multisafepay' ), esc_html__( 'Invalid request', 'multisafepay' ), 400 );
            }
        }
        $payment_method_callback = ( new PaymentMethodCallback( $_GET['transactionid'] ) )->process_callback();
    }

    /**
     * This validates that the API Key has been setup properly
     * check SDK, and check if the gateway is enable for the merchant.
     *
     * @param string $key
     * @param string $value
     *
     * @return  string
     */
    public function validate_enabled_field( $key, $value ) {
        if ( null === $value ) {
            return 'no';
        }
        $gateways           = ( new SdkService() )->get_gateways();
        $available_gateways = array();
        foreach ( $gateways as $gateway ) {
            $available_gateways[] = $gateway->getId();
        }
        if ( ! in_array( $this->gateway_code, $available_gateways, true ) ) {
            $message = sprintf(
                /* translators: %1$: The payment method title */
                __( 'It seems %1$s is not available for your MultiSafepay account. <a href="%2$s">Contact support</a>', 'multisafepay' ),
                $this->get_payment_method_title(),
                admin_url( 'admin.php?page=multisafepay-settings&tab=support' )
            );
            $this->add_error( $message );
        }

        return 'yes';
    }

    /**
     * Prints checkout custom fields
     *
     * @return  void
     */
    public function payment_fields(): void {
        require $this->plugin_dir_path . 'templates/multisafepay-checkout-fields-display.php';
    }

    /**
     * Validate_fields
     *
     * @return  boolean
     */
    public function validate_fields(): bool {

        if (
            ( isset( $_POST['woocommerce-process-checkout-nonce'] ) && wp_verify_nonce( $_POST['woocommerce-process-checkout-nonce'], 'woocommerce-process_checkout' ) ) ||
            ( isset( $_POST['woocommerce-pay-nonce'] ) && wp_verify_nonce( $_POST['woocommerce-pay-nonce'], 'woocommerce-pay' ) )
        ) {

            if ( ( isset( $_POST[ $this->id . '_salutation' ] ) ) && '' === $_POST[ $this->id . '_salutation' ] ) {
                wc_add_notice( __( 'Salutation is a required field', 'multisafepay' ), 'error' );
            }

            if ( ( isset( $_POST[ $this->id . '_gender' ] ) ) && '' === $_POST[ $this->id . '_gender' ] ) {
                wc_add_notice( __( 'Gender is a required field', 'multisafepay' ), 'error' );
            }

            if ( isset( $_POST[ $this->id . '_birthday' ] ) && '' === $_POST[ $this->id . '_birthday' ] ) {
                wc_add_notice( __( 'Date of birth is a required field', 'multisafepay' ), 'error' );
            }

            if ( isset( $_POST[ $this->id . '_bank_account' ] ) && '' === $_POST[ $this->id . '_bank_account' ] ) {
                wc_add_notice( __( 'Bank Account is a required field', 'multisafepay' ), 'error' );
            }

            if ( isset( $_POST[ $this->id . '_bank_account' ] ) && '' !== $_POST[ $this->id . '_bank_account' ] ) {
                if ( ! $this->validate_iban( $_POST[ $this->id . '_bank_account' ] ) ) {
                    wc_add_notice( __( 'IBAN does not seems valid', 'multisafepay' ), 'error' );
                }
            }

            if ( isset( $_POST[ $this->id . '_account_holder_name' ] ) && '' === $_POST[ $this->id . '_account_holder_name' ] ) {
                wc_add_notice( __( 'Account holder is a required field', 'multisafepay' ), 'error' );
            }

            if ( isset( $_POST[ $this->id . '_account_holder_iban' ] ) && '' === $_POST[ $this->id . '_account_holder_iban' ] ) {
                wc_add_notice( __( 'IBAN is a required field', 'multisafepay' ), 'error' );
            }

            if ( isset( $_POST[ $this->id . '_account_holder_iban' ] ) && '' !== $_POST[ $this->id . '_account_holder_iban' ] ) {
                if ( ! $this->validate_iban( $_POST[ $this->id . '_account_holder_iban' ] ) ) {
                    wc_add_notice( __( 'IBAN does not seems valid', 'multisafepay' ), 'error' );
                }
            }

            if ( wc_get_notices( 'error' ) ) {
                return false;
            }

            return true;

        }

    }

    /**
     * Returns bool after validates IBAN format
     *
     * @param string $iban
     *
     * @return  boolean
     */
    public function validate_iban( $iban ): bool {
        try {
            $iban = new IbanNumber( $iban );

            return true;
        } catch ( InvalidArgumentException $invalid_argument_exception ) {
            return false;
        }
    }

    /**
     * Returns the WooCommerce registered order statuses
     *
     * @see     http://hookr.io/functions/wc_get_order_statuses/
     *
     * @return  array
     */
    private function get_order_statuses(): array {
        $order_statuses               = wc_get_order_statuses();
        $order_statuses['wc-default'] = __( 'Default value set in common settings', 'multisafepay' );

        return $order_statuses;
    }

    /**
     * Validate the gatewayinfo, return true if validation is successful
     *
     * @param GatewayInfoInterface $gateway_info
     *
     * @return boolean
     */
    public function validate_gateway_info( GatewayInfoInterface $gateway_info ): bool {
        return true;
    }

    /**
     * @param \WC_Order $order
     *
     * @return bool
     */
    public function can_refund_order( $order ) {
        if ( in_array( $order->get_status(), RefundService::NOT_ALLOW_REFUND_ORDER_STATUSES, true ) ) {
            return false;
        }

        return $order && $this->supports( 'refunds' );
    }

}
