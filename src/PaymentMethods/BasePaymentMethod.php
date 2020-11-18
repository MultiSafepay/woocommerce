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

use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Sdk;
use MultiSafepay\WooCommerce\Services\OrderService;
use WC_Countries;
use WC_Payment_Gateway;

abstract class BasePaymentMethod extends WC_Payment_Gateway implements PaymentMethodInterface
{
    /**
     * @var TransactionManager
     */
    protected $transaction_manager;

    /**
     * @var OrderService
     */
    protected $order_service;

    /**
     * @var string
     */
    protected $gateway_code;

    /**
     * What type of transaction, should be 'direct' or 'redirect'
     *
     * @var string
     */
    protected $type;

    /**
     * Construct for Core class.
     */
    public function __construct()
    {
        $this->supports = array('products', 'refunds');

        $this->id = $this->get_payment_method_id();
        $this->type = $this->get_payment_method_type();
        $this->method_title = $this->get_payment_method_title();
        $this->method_description = $this->get_payment_method_description();
        $this->type = $this->get_payment_method_type();
        $this->gateway_code = $this->get_payment_method_code();
        $this->has_fields = $this->has_fields();
        $this->checkout_fields_ids = $this->get_checkout_fields_ids();
        $this->gateway_info = $this->get_gateway_info();
        $this->icon = esc_url( plugins_url( '/assets/public/img/' .  $this->get_payment_method_icon(), dirname(__DIR__ ) ) );

        $this->add_form_fields();

        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title', $this->get_method_title());
        $this->description = $this->get_option('description');
        $this->max_amount = $this->get_option('max_amount');
        $this->min_amount = $this->get_option('min_amount');
        $this->countries    = $this->get_option('countries');

        $this->plugin_dir_path = plugin_dir_path( dirname(__DIR__) );

        $this->errors = array();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Return an array of allowed countries defined in WooCommerce Settings.
     *
     * @return array
     */
    private function get_countries(): array
    {
        $countries = new WC_Countries();
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
        return array( );
    }

    /**
     * Return the gateway info
     *
     * @return string
     */
    public function get_gateway_info(): string {
        return '';
    }

    /**
     * Define the form option - settings fields.
     *
     * @return  void
     */
    public function add_form_fields(): void
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable ' . $this->get_method_title() . ' Gateway',
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'multisafepay'),
                'default' => $this->get_method_title(),
            ),
            'description' => array(
                'title' => __('Description', 'multisafepay'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'multisafepay'),
                'default' => ''
            ),
            'min_amount' => array(
                'title' => __('Min Amount', 'multisafepay'),
                'type' => 'decimal',
                'description' => __('This payment method is not shown in the checkout if the order total is lower than the defined amount. Leave black for no restrictions.', 'multisafepay'),
                'default' => $this->get_option('min_amount', ''),
            ),
            'max_amount' => array(
                'title' => __('Max Amount', 'multisafepay'),
                'type' => 'decimal',
                'description' => __('This payment method is not shown in the checkout if the order total exceeds a certain amount. Leave black for no restrictions.', 'multisafepay'),
                'default' => $this->get_option('max_amount', ''),
            ),
            'countries' => array(
                'title' => __('Country', 'multisafepay'),
                'type' => 'multiselect',
                'description' => __('If you select one or more countries, this payment method won\'t show in the checkout page, if the payment address`s country of the customer match with the selected values. Leave black for no restrictions.', 'multisafepay'),
                'desc_tip' => __('For most operating system and configurations, you must hold Ctrl + D or Cmd + D on your keyboard, to select more than one value.', 'multisafepay'),
                'options' => $this->get_countries(),
                'default' => $this->get_option('countries', array()),
            )
        );
    }

    /**
     * Process the payment and return the result.
     *
     * @param integer $order_id Order ID.
     * @return  array|mixed|void
     */
    public function process_payment($order_id): array
    {
        $sdk = new Sdk(get_option('multisafepay_api_key'), get_option('multisafepay_testmode') === 'no');
        $transaction_manager = $sdk->getTransactionManager();
        $order_service = new OrderService();
        $order_request = $order_service->create_order_request($order_id, $this->gateway_code, $this->type);
        $transaction = $transaction_manager->create($order_request);

        return array(
            'result' => 'success',
            'redirect' => esc_url_raw($transaction->getPaymentUrl()),
        );
    }

    /**
     * Process the refund.
     *
     * @param integer $order_id Order ID.
     * @param float $amount Amount to be refunded.
     * @param string $reason Reason description.
     * @return  boolean
     * @todo This function needs more work to process the refund.
     *
     */
    public function process_refund($order_id, $amount = null, $reason = ''): bool
    {
        return false;
    }

    /**
     * This validates that the API Key has been setup properly
     * check SDK, and check if the gateway is enable for the merchant.
     *
     * @param string $key
     * @param string $value
     * @return  string
     * @todo This function needs more work checking if API key works on the SDK.
     *
     */
    public function validate_enabled_field(string $key, string $value): string
    {
        return $value !== null ? 'yes' : 'no';
    }


    /**
     * Prints checkout custom fields
     *
     * @return  void
     */
    public function payment_fields(): void
    {
        $issuers        = array();
        require($this->plugin_dir_path . 'templates/multisafepay-checkout-fields-display.php');
    }

    /**
     * Validate_fields
     *
     * @return  boolean
     */
    public function validate_fields(): bool {

        if ( (isset($_POST['multisafepay_' . $this->id . '_gender'])) && $_POST['multisafepay_' . $this->id . '_gender'] === '') {
            wc_add_notice(  __('Gender is a required field', 'multisafepay'), 'error' );
        }

        if (isset($_POST['multisafepay_' . $this->id . '_birthday']) && $_POST['multisafepay_' . $this->id . '_birthday'] === '' ) {
            wc_add_notice(  __('Date of birth is a required field', 'multisafepay'), 'error' );
        }

        if (isset($_POST['multisafepay_' . $this->id . '_bank_account']) && $_POST['multisafepay_' . $this->id . '_bank_account'] === '' ) {
            wc_add_notice(  __('Bank Account is a required field', 'multisafepay'), 'error' );
        }

        if (isset($_POST['multisafepay_' . $this->id . '_bank_account']) && $_POST['multisafepay_' . $this->id . '_bank_account'] !== '' ) {
            if (!$this->validate_iban($_POST['multisafepay_' . $this->id . '_bank_account'])) {
                wc_add_notice(  __('IBAN does not seems valid', 'multisafepay'), 'error' );
            }
        }

        if (isset($_POST['multisafepay_' . $this->id . '_account_holder_name']) && $_POST['multisafepay_' . $this->id . '_account_holder_name'] === '' ) {
            wc_add_notice(  __('Account holder is a required field', 'multisafepay'), 'error' );
        }

        if (isset($_POST['multisafepay_' . $this->id . '_account_holder_iban']) && $_POST['multisafepay_' . $this->id . '_account_holder_iban'] === '' ) {
            wc_add_notice(  __('IBAN is a required field', 'multisafepay'), 'error' );
        }

        if (isset($_POST['multisafepay_' . $this->id . '_account_holder_iban']) && $_POST['multisafepay_' . $this->id . '_account_holder_iban'] !== '' ) {
            if (!$this->validate_iban($_POST['multisafeay_' . $this->id . '_account_holder_iban'])) {
                wc_add_notice(  __('IBAN does not seems valid', 'multisafepay'), 'error' );
            }
        }

        if( wc_get_notices( 'error' ) ) {
            return false;
        }

        return true;
    }

    /**
     * Returns bool after validates IBAN format
     *
     * @param   string  $iban
     * @return  boolean
     *
     */
    public function validate_iban($iban): bool {
        try {
            $iban = new IbanNumber($iban);
            return true;
        }
        catch (\MultiSafepay\Exception\InvalidArgumentException $invalidArgumentException ) {
            return false;
        }
    }

}