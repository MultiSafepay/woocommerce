<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Issuer as MyBankGatewayInfo;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\Services\IssuerService;

class MyBank extends BasePaymentMethod {

    /**
     * ApplePay constructor.
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
    }

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_mybank';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'MYBANK';
    }

    /**
     * @return string
     */
    public function get_payment_method_type(): string {
        return ( $this->get_option( 'direct', 'yes' ) === 'yes' ) ? 'direct' : 'redirect';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'MyBank - Bonifico Immediato';
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
        /* translators: %2$: The payment method title */
            __( 'Leading e-authorization solution in Italy for instant bank transfers. <br />Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay' ),
            'https://docs.multisafepay.com/docs/mybank',
            $this->get_payment_method_title()
        );
        return $method_description;
    }

    /**
     * @return boolean
     */
    public function has_fields(): bool {
        return ( $this->get_option( 'direct', 'yes' ) === 'yes' ) ? true : false;
    }

    /**
     * @return array
     */
    public function add_form_fields(): array {
        $form_fields           = parent::add_form_fields();
        $form_fields['direct'] = array(
            'title'    => __( 'Transaction Type', 'multisafepay' ),
            /* translators: %1$: The payment method title */
            'label'    => sprintf( __( 'Enable direct %1$s', 'multisafepay' ), $this->get_payment_method_title() ),
            'type'     => 'checkbox',
            'default'  => 'yes',
            'desc_tip' => __( 'If enabled, additional information can be entered during WooCommerce checkout. If disabled, additional information will be requested on the MultiSafepay payment page.', 'multisafepay' ),
        );
        return $form_fields;
    }

    /**
     * @return array
     */
    public function get_checkout_fields_ids(): array {
        return array( 'mybank_issuers' );
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'mybank.png';
    }

    /**
     * Prints checkout custom fields
     *
     * @return  void
     */
    public function payment_fields(): void {
        $issuer_service = new IssuerService();
        $issuers        = $issuer_service->get_issuers( $this->get_payment_method_code() );
        require MULTISAFEPAY_PLUGIN_DIR_PATH . 'templates/multisafepay-checkout-fields-display.php';
    }

    /**
     * @param array|null $data
     * @return MyBankGatewayInfo
     */
    public function get_gateway_info( array $data = null ): GatewayInfoInterface {
        $gateway_info = new MyBankGatewayInfo();
        if ( isset( $_POST[ $this->id . '_issuer_id' ] ) ) {
            $gateway_info->addIssuerId( sanitize_key( $_POST[ $this->id . '_issuer_id' ] ) );
        }
        return $gateway_info;
    }

    /**
     * Check if issuer_id has been set
     *
     * @param GatewayInfoInterface $gateway_info
     * @return boolean
     */
    public function validate_gateway_info( GatewayInfoInterface $gateway_info ): bool {
        $data = $gateway_info->getData();
        if ( empty( $data['issuer_id'] ) ) {
            $this->type = 'redirect';
            return false;
        }
        return true;
    }

    /**
     * Enqueue Javascript to convert issuer dropdown into a SelectWoo field
     *
     * @return void
     */
    public function enqueue_script(): void {
        if ( is_checkout() ) {
            wp_enqueue_script( 'multisafepay-my-bank-js', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-my-bank.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
        }
    }

}
