<?php

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
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) 2020 MultiSafepay, Inc. (http://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MultiSafepay\WooCommerce;

use MultiSafepay\WooCommerce\Api\Client;
use MultiSafepay\WooCommerce\Gateway\Afterpay;
use MultiSafepay\WooCommerce\Gateway\Alipay;
use MultiSafepay\WooCommerce\Gateway\Amex;
use MultiSafepay\WooCommerce\Gateway\Applepay;
use MultiSafepay\WooCommerce\Gateway\Bancontact;
use MultiSafepay\WooCommerce\Gateway\Banktrans;
use MultiSafepay\WooCommerce\Gateway\Beautyandwellness;
use MultiSafepay\WooCommerce\Gateway\Belfius;
use MultiSafepay\WooCommerce\Gateway\Boekenbon;
use MultiSafepay\WooCommerce\Gateway\Creditcard;
use MultiSafepay\WooCommerce\Gateway\Dirdeb;
use MultiSafepay\WooCommerce\Gateway\Directbanktransfer;
use MultiSafepay\WooCommerce\Gateway\Dotpay;
use MultiSafepay\WooCommerce\Gateway\Einvoice;
use MultiSafepay\WooCommerce\Gateway\Eps;
use MultiSafepay\WooCommerce\Gateway\Fashioncheque;
use MultiSafepay\WooCommerce\Gateway\Fashiongiftcard;
use MultiSafepay\WooCommerce\Gateway\Fastcheckout;
use MultiSafepay\WooCommerce\Gateway\Fietsbon;
use MultiSafepay\WooCommerce\Gateway\Gezondheidsbon;
use MultiSafepay\WooCommerce\Gateway\Giropay;
use MultiSafepay\WooCommerce\Gateway\Givacard;
use MultiSafepay\WooCommerce\Gateway\Goodcard;
use MultiSafepay\WooCommerce\Gateway\Ideal;
use MultiSafepay\WooCommerce\Gateway\Ing;
use MultiSafepay\WooCommerce\Gateway\Kbc;
use MultiSafepay\WooCommerce\Gateway\Klarna;
use MultiSafepay\WooCommerce\Gateway\Maestro;
use MultiSafepay\WooCommerce\Gateway\Mastercard;
use MultiSafepay\WooCommerce\Gateway\Nationalebioscoopbon;
use MultiSafepay\WooCommerce\Gateway\Nationaletuinbon;
use MultiSafepay\WooCommerce\Gateway\Ohmygood;
use MultiSafepay\WooCommerce\Gateway\Parfumcadeaukaart;
use MultiSafepay\WooCommerce\Gateway\Payafter;
use MultiSafepay\WooCommerce\Gateway\Paypal;
use MultiSafepay\WooCommerce\Gateway\Paysafecard;
use MultiSafepay\WooCommerce\Gateway\Podiumcadeaukaart;
use MultiSafepay\WooCommerce\Gateway\Santander;
use MultiSafepay\WooCommerce\Gateway\Sofort;
use MultiSafepay\WooCommerce\Gateway\Sportenfit;
use MultiSafepay\WooCommerce\Gateway\Trustly;
use MultiSafepay\WooCommerce\Gateway\Visa;
use MultiSafepay\WooCommerce\Gateway\Vvvcadeaukaart;
use MultiSafepay\WooCommerce\Gateway\Webshopgiftcard;
use MultiSafepay\WooCommerce\Gateway\Wellnessgiftcard;
use MultiSafepay\WooCommerce\Gateway\Wijncadeau;
use MultiSafepay\WooCommerce\Gateway\Winkelcheque;
use MultiSafepay\WooCommerce\Gateway\Yourgift;
use MultiSafepay\WooCommerce\Helper\Helper;

class Gateways
{

    public static function register()
    {
        add_filter('woocommerce_payment_gateways', array(__CLASS__, '_getGateways'));
        add_filter('woocommerce_payment_gateways_settings', array(__CLASS__, '_addGlobalSettings'), 1);

        add_action('wp_loaded', array(__CLASS__, 'MultiSafepay_Response'));
        add_action('init', array(__CLASS__, 'addFCO'));
        add_action('woocommerce_api_' . strtolower(get_class()), array(__CLASS__, 'doFastCheckout'));

        add_action('woocommerce_payment_complete', array(__CLASS__, 'getRealPaymentMethod'), 10, 1);

        global $wpdb;
        $wpdb->hide_errors();

        $collate = '';
        if ($wpdb->has_cap('collation')) {
            if (!empty($wpdb->charset)) {
                $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
            }
            if (!empty($wpdb->collate)) {
                $collate .= " COLLATE $wpdb->collate";
            }
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $woocommerce_tables = "CREATE TABLE {$wpdb->prefix}woocommerce_multisafepay
                                (   id bigint(20) NOT NULL auto_increment,
                                    trixid varchar(200) NOT NULL,
                                    orderid varchar(200) NOT NULL,
                                    status varchar(200) NOT NULL,
                                    PRIMARY KEY  (id)
                                ) $collate;";
        dbDelta($woocommerce_tables);
    }


    public static function getRealPaymentMethod()
    {
        global $wpdb;

        $trns_id = filter_input(INPUT_GET, 'transactionid', FILTER_SANITIZE_STRING);

        if (empty($trns_id)) {
            return;
        }

        // Get real payment method
        //
        $msp    = new Client();
        $helper = new Helper();

        $msp->setApiKey($helper->getApiKey());
        $msp->setApiUrl($helper->getTestMode());

        try {
            $msg = null;
            $transactie = $msp->orders->get($trns_id, 'orders', array(), false);
        } catch (\Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            $helper->write_log($msg);
            return;
        }

        if ($transactie->fastcheckout == 'NO' &&  isset($transactie->var2)) {
            $order_id = $transactie->var2;
        } else {
            $tablename = $wpdb->prefix . 'woocommerce_multisafepay';
            $sql = $wpdb->prepare("SELECT orderid FROM {$tablename} WHERE trixid = %s", $trns_id);
            $order_id = $wpdb->get_var($sql);
        }
        $order = wc_get_order($order_id);


        $payment_method       = false;
        $payment_method_title = false;
        $gateway              = $transactie->payment_details->type;

        $tablename = $wpdb->prefix . 'options';
        $results   = $wpdb->get_results("SELECT option_name, option_value FROM {$tablename} WHERE `option_name` like 'woocommerce_multisafepay_%'");

        foreach ($results as $result) {
            $options = get_option($result->option_name, array());

            if (isset($options['gateway'])  && ( $gateway == $options['gateway'])) {
                preg_match('/woocommerce_(.*)_settings/', $result->option_name, $matches);
                $payment_method         = $matches[1];
                $payment_method_title   = $options['title'];
                break;
            }
        }

        // Initial payment method differs from real payment method.
        if ($payment_method != false && get_post_meta($order_id, '_payment_method', true) != $payment_method) {
            $order->add_order_note(sprintf(__('Payment started with %s, but paid with %s', 'multisafepay'), get_post_meta($order_id, '_payment_method_title', true), $payment_method_title));

            update_post_meta($order_id, '_payment_method', $payment_method);
            update_post_meta($order_id, '_payment_method_title', $payment_method_title);
        }
    }



    public static function _getGateways($arrDefault)
    {
        $paymentOptions = array(
            Afterpay::class,
            Alipay::class,
            Amex::class,
            Applepay::class,
            Bancontact::class,
            Banktrans::class,
            Belfius::class,
            Creditcard::class,
            Dirdeb::class,
            Belfius::class,
            Directbanktransfer::class,
            Dotpay::class,
            Einvoice::class,
            Eps::class,
            Giropay::class,
            Ideal::class,
            Ing::class,
            Kbc::class,
            Klarna::class,
            Maestro::class,
            Mastercard::class,
            Payafter::class,
            Paypal::class,
            Paysafecard::class,
            Santander::class,
            Sofort::class,
            Trustly::class,
            Visa::class
        );

        $giftCards = array(
            Beautyandwellness::class,
            Nationalebioscoopbon::class,
            Boekenbon::class,
            Fashioncheque::class,
            Fashiongiftcard::class,
            Fietsbon::class,
            Gezondheidsbon::class,
            Givacard::class,
            Goodcard::class,
            Nationaletuinbon::class,
            Ohmygood::class,
            Parfumcadeaukaart::class,
            Podiumcadeaukaart::class,
            Sportenfit::class,
            Vvvcadeaukaart::class,
            Webshopgiftcard::class,
            Wellnessgiftcard::class,
            Wijncadeau::class,
            Winkelcheque::class,
            Yourgift::class
        );


        $giftcards_enabled = get_option('multisafepay_giftcards_enabled') == 'yes' ? true : false;
        if ($giftcards_enabled) {
            $paymentOptions = array_merge($paymentOptions, $giftCards);
        }

        $paymentOptions = array_merge($arrDefault, $paymentOptions);

        return $paymentOptions;
    }

    public static function _addGlobalSettings($settings)
    {
        $updatedSettings = array();
        $addedSettings = array();

        $addedSettings[] = array(
            'title'     => __('MultiSafepay settings', 'multisafepay'),
            'type'      => 'title',
            'desc'      => '<p>' . __('The following options are needed to make use of the MultiSafepay plugin', 'multisafepay') . '</p>',
            'id'        => 'multisafepay_general_settings'
        );
        $addedSettings[] = array(
            'name'      => __('API key', 'multisafepay'),
            'type'      => 'text',
            'desc_tip'  => __('Copy the API key from your MultiSafepay Control', 'multisafepay'),
            'id'        => 'multisafepay_api_key',
            'css'       => 'min-width:350px;',
        );
        $addedSettings[] = array(
            'name'      => __('Test Mode', 'multisafepay'),
            'desc'      => sprintf(__('Activate %s', 'multisafepay'), __('Test Mode', 'multisafepay')),
            'type'      => 'checkbox',
            'default'   => 'yes',
            'desc_tip'  => __('Enable if the API key belongs to a MultiSafepay test account.', 'multisafepay'),
            'id'        => 'multisafepay_testmode'
        );

        $addedSettings[] = array(
            'name'      => __('Fastcheckout', 'multisafepay'),
            'desc'      => sprintf(__('Activate %s', 'multisafepay'), __('Fastcheckout', 'multisafepay')),
            'type'      => 'checkbox',
            'default'   => 'no',
            'desc_tip'  => sprintf(__('When enabled %s will be available during checkout.', 'multisafepay'), __('Fastcheckout', 'multisafepay')),
            'id'        => 'multisafepay_fco_enabled'
        );

        $addedSettings[] = array(
            'name'      => __('Gift cards', 'multisafepay'),
            'desc'      => sprintf(__('Activate %s', 'multisafepay'), __('Gift cards', 'multisafepay')),
            'type'      => 'checkbox',
            'default'   => 'no',
            'desc_tip'  => sprintf(__('When enabled %s will be available during checkout.', 'multisafepay'), __('Gift cards', 'multisafepay')),
            'id'        => 'multisafepay_giftcards_enabled'
        );

        $addedSettings[] = array(
            'name'      => __('Expire order', 'multisafepay'),
            'type'      => 'number',
            'default'   => 30,
            'desc_tip'  => __('The time before an unfinished order is set to expire', 'multisafepay'),
            'id'        => 'multisafepay_time_active',
            'css'       => 'max-width:80px;',
        );
        $addedSettings[] = array(
            'type'      => 'select',
            'options'   => array(   'days'      => __('days', 'multisafepay'),
                                    'hours'     => __('hours', 'multisafepay'),
                                    'seconds'   => __('seconds', 'multisafepay')),
            'id'        => 'multisafepay_time_unit',
        );
        $addedSettings[] = array(
            'name'      => __('Images', 'multisafepay'),
            'desc'      => __('Show gateway images', 'multisafepay'),
            'type'      => 'checkbox',
            'default'   => 'yes',
            'id'        => 'multisafepay_show_images',
            'desc_tip'  => sprintf(__('%s during checkout.', 'multisafepay'), __('Show gateway images', 'multisafepay'))
        );

        $addedSettings[] = array(
            'name'      => __('Analytics', 'multisafepay'),
            'desc'      => __('Google Analytics', 'multisafepay'),
            'type'      => 'text',
            'desc_tip'  => __('Your Google Analytics Tracking Code', 'multisafepay'),
            'id'        => 'multisafepay_ga',
        );

        $addedSettings[] = array(
            'name'      => __('Debug', 'multisafepay'),
            'desc'      => __('Activate debug mode', 'multisafepay'),
            'type'      => 'checkbox',
            'default'   => 'no',
            'desc_tip'  => __('Enable to log transactions to a logfile (enabling WordPress debug mode is required)', 'multisafepay'),
            'id'        => 'multisafepay_debugmode',
        );
        $addedSettings[] = array(
            'name'      => __('Notification URL', 'multisafepay'),
            'type'      => 'text',
            'default'   => sprintf('%s/?page=multisafepaynotify', site_url()),
            'desc'      => __('Copy-paste this URL to your website configuration Notification URL in your MultiSafepay Control.', 'multisafepay'),
            'id'        => 'multisafepay_nurl',
            'desc_tip'  => true,
            'css'       => 'min-width:800px;',
        );

        $addedSettings[] = array(
            'type'      => 'sectionend',
            'id'        => 'multisafepay_general_settings',
        );
        foreach ($settings as $setting) {
            if (isset($setting['id']) && $setting['id'] == 'payment_gateways_options' && $setting['type'] != 'sectionend') {
                $updatedSettings = array_merge($updatedSettings, $addedSettings);
            }
            $updatedSettings[] = $setting;
        }

        return $updatedSettings;
    }

    public function validate_fields()
    {
        return true;
    }

    /**
     * @return bool|void
     */
    public static function Multisafepay_Response()
    {
        $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);
        $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
        $trns_id = filter_input(INPUT_GET, 'transactionid', FILTER_SANITIZE_STRING);
        $identifier = filter_input(INPUT_GET, 'identifier', FILTER_SANITIZE_STRING);
        $cancel_order = filter_input(INPUT_GET, 'cancel_order', FILTER_SANITIZE_STRING);

        // If not initialized by MultiSafepay
        if ($page != 'multisafepaynotify') {
            return true;
        }
        if (empty($trns_id) && empty($identifier)) {
            return;
        }

        global $wpdb, $woocommerce;

        $redirect = false;

        switch ($type) {
            case 'redirect':
                $redirect = true;
                break;
            case 'cancel':
                return true;
            case 'shipping':
                $fco = new Fastcheckout();
                print_r($fco->get_shipping_methods_xml());
                exit;
            default:
                break;
        }

        // If no transaction-id there is nothing to process..
        if (empty($trns_id)) {
            return;
        }

        $msp = new Client();
        $helper = new Helper();

        $msp->setApiKey($helper->getApiKey());
        $msp->setApiUrl($helper->getTestMode());

        try {
            $msg = null;
            $transactie = $msp->orders->get($trns_id, 'orders', array(), false);
        } catch (\Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            $helper->write_log($msg);
            return;
        }

        $updated = false;
        $status = $transactie->status;

        if ($transactie->fastcheckout == 'NO' && isset($transactie->var2)) {
            $order_id = $transactie->var2;
        } else {
            $tablename = $wpdb->prefix . 'woocommerce_multisafepay';
            $sql = $wpdb->prepare("SELECT orderid FROM {$tablename} WHERE trixid = %s", $trns_id);
            $order_id = $wpdb->get_var($sql);
        }
        $order = wc_get_order($order_id);

        if ($cancel_order && ($status != 'completed')) {
            $order->update_status('wc-cancelled');
            $location = wc_get_cart_url();
            wp_safe_redirect($location);
            exit();
        }

        $amount = $transactie->amount / 100;
        $gateway = $transactie->payment_details->type;

        if ($transactie->fastcheckout == 'YES' && empty($order_id)) {
            // No correct transaction, go back to checkout-page.
            if (empty($transactie->transaction_id)) {
                wp_safe_redirect(wc_get_cart_url());
                exit();
            }

            $amount = $transactie->amount / 100;

            if (!empty($transactie->shopping_cart)) {
                $order = wc_create_order();

                // Compatiblity Woocommerce 2.x and 3.x
                $order_id = (method_exists($order, 'get_id')) ? $order->get_id() : $order->id;

                $wpdb->query('INSERT INTO ' . $wpdb->prefix . 'woocommerce_multisafepay' . " (trixid, orderid, status) VALUES ('" . $trns_id . "', '" . $order_id . "', '" . $status . "'  )");

                $billing_address = array();
                $billing_address['first_name'] = $transactie->customer->first_name;
                $billing_address['last_name'] = $transactie->customer->last_name;
                $billing_address['address_1'] = $transactie->customer->address1 . $transactie->customer->house_number;
                $billing_address['address_2'] = $transactie->customer->address2;
                $billing_address['city'] = $transactie->customer->city;
                $billing_address['state'] = $transactie->customer->state;
                $billing_address['postcode'] = $transactie->customer->zip_code;
                $billing_address['country'] = $transactie->customer->country;
                $billing_address['phone'] = $transactie->customer->phone1;
                $billing_address['email'] = $transactie->customer->email;

                $shipping_address['first_name'] = $transactie->delivery->first_name;
                $shipping_address['last_name'] = $transactie->delivery->last_name;
                $shipping_address['address_1'] = $transactie->delivery->address1 . $transactie->delivery->house_number;
                $shipping_address['address_2'] = $transactie->delivery->address2;
                $shipping_address['city'] = $transactie->delivery->city;
                $shipping_address['state'] = $transactie->delivery->state;
                $shipping_address['postcode'] = $transactie->delivery->zip_code;
                $shipping_address['country'] = $transactie->delivery->country;

                $order->set_address($billing_address, 'billing');
                $order->set_address($shipping_address, 'shipping');

                // Add shipping method
                foreach ($woocommerce->shipping->load_shipping_methods() as $shipping_method) {
                    if ($shipping_method->method_title == $transactie->order_adjustment->shipping->flat_rate_shipping->name) {
                        $item = new \WC_Order_Item_Shipping();
                        $item->set_props(
                            array(
                            'method_title' => $transactie->order_adjustment->shipping->flat_rate_shipping->name,
                            'method_id' => $shipping_method->id,
                            'total' => wc_format_decimal($transactie->order_adjustment->shipping->flat_rate_shipping->cost),
                            'taxes' => $shipping_method->taxes,
                            'order_id' => $order_id,
                            )
                        );
                        $order->add_item($item);
                        break;
                    }
                }

                $order->add_order_note($transactie->transaction_id);

                // Add payment method
                $gateways = new \WC_Payment_Gateways();
                $all_gateways = $gateways->get_available_payment_gateways();

                // Set default
                $selected_gateway = 'MultiSafepay';
                foreach ($all_gateways as $gateway) {
                    if ($gateway->id == strtolower('multisafepay_' . $transactie->payment_details->type)) {
                        $selected_gateway = $gateway;
                        break;
                    }
                }
                $order->set_payment_method($selected_gateway);

                // Temp array needed for tax calculating coupons etc...
                $tmp_tax = array();
                foreach ($transactie->checkout_options->alternate as $tax) {
                    $tmp_tax[$tax->name] = $tax->rules[0]->rate;
                }

                // TODO: Check if products are filled correctly
                foreach ($transactie->shopping_cart->items as $product) {
                    $sku = json_decode($product->merchant_item_id);

                    // Product
                    $product_id = null;
                    if (!empty($sku->sku)) {
                        $product_id = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1",
                                $sku->sku
                            )
                        );
                    } elseif (!empty($sku->id)) {
                        $product_id = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT post_id FROM $wpdb->postmeta WHERE post_id='%s' LIMIT 1",
                                $sku->id
                            )
                        );
                    }

                    if ($product_id) {
                        $product_item = new \WC_Product($product_id);
                        $product_item->qty = $product->quantity;
                        $order->add_product($product_item, $product->quantity);
                    }

                    // CartCoupon
                    $applied_discount_tax = 0;

                    if (!empty($sku->{'Coupon-code'})) {
                        $code = $sku->Coupon - code;
                        $unit_price = (float) str_replace('-', '', $product->unit_price);
                        update_post_meta($order_id, '_cart_discount', $unit_price);
                        update_post_meta($order_id, '_order_total', $amount);
                        update_post_meta($order_id, '_cart_discount_tax', 0);

                        $order->calculate_taxes();
                        $tax_percentage = 0;
                        $order_data = get_post_meta($order_id);
                        $new_order_tax = round(
                            $order_data['_order_tax'][0] - (($unit_price * (1 + $tax_percentage)) - $unit_price),
                            2
                        );
                        update_post_meta($order_id, '_order_tax', $new_order_tax);
                        $order->add_coupon($code, $unit_price, $applied_discount_tax);
                    }
                }

                update_post_meta($order_id, '_order_total', $transactie->amount / 100);
                $order->calculate_taxes();

                foreach ($order->get_items('tax') as $key => $value) {
                    $data = wc_get_order_item_meta($key, 'tax_amount');
                    wc_update_order_item_meta($key, 'tax_amount', $data - $applied_discount_tax);
                }
            }
        }

        if (!$order) {
            exit('Order does not exist');
        }

        $orderStatus = $order->get_status();

        switch ($status) {
            case 'cancelled':
                $order->update_status('wc-cancelled');
                $order->add_order_note(sprintf(__('MultiSafepay payment status %s', 'multisafepay'), $status));
                $updated = true;
                break;
            case 'initialized':
                if ($gateway == 'BANKTRANS') {
                    $order->update_status(
                        'wc-on-hold',
                        sprintf(__('Bank transfer: awaiting payment', 'multisafepay'), $amount)
                    );
                    $return_url = $order->get_checkout_order_received_url();
                    $updated = true;
                    break;
                } else {
                    $order->update_status('wc-pending');
                    $order->add_order_note(sprintf(__('MultiSafepay payment status %s', 'multisafepay'), $status));
                    $updated = true;
                    break;
                }
            case 'completed':
                if ($order->get_total() != $amount) {
                    if ($orderStatus != 'processing') {
                        $order->update_status(
                            'wc-on-hold',
                            sprintf(__('Validation error: MultiSafepay amounts do not match (gross %s).', 'multisafepay'), $amount)
                        );
                    }
                }

                if ($orderStatus != 'processing' && $orderStatus != 'completed' && $orderStatus != 'wc-completed') {
                    $order->payment_complete();
                    $woocommerce->cart->empty_cart();
                } else {
                    $updated = true;
                }
                if ($status == 'completed' && $gateway == 'KLARNA') {
                    $order->add_order_note(
                        __('Klarna reservation number: ', 'multisafepay') . $transactie->payment_details->external_transaction_id
                    );
                }
                break;
            case 'refunded':
                if ($order->get_total() == $amount) {
                    $order->update_status(
                        'wc-refunded',
                        sprintf(__('Payment %s via MultiSafepay.', 'multisafepay'), strtolower($status))
                    );
                    $order->add_order_note(sprintf(__('MultiSafepay payment status %s', 'multisafepay'), $status));
                }
                $updated = true;
                break;
            case 'uncleared':
                if ($orderStatus == 'on-hold') {
                    break;
                }

                $order->update_status('wc-on-hold');
                $order->add_order_note(sprintf(__('MultiSafepay payment status %s', 'multisafepay'), $status));
                $updated = true;
                break;
            case 'reserved':
            case 'declined':
            case 'expired':
                // Only change the orderstatus if the current status is pending or on-hold
                if ($orderStatus == 'pending' || $orderStatus == 'on-hold') {
                    $order->update_status(
                        'wc-failed',
                        sprintf(__('Payment %s via MultiSafepay.', 'multisafepay'), strtolower($status))
                    );
                    $order->add_order_note(sprintf(__('MultiSafepay payment status %s', 'multisafepay'), $status));
                    $updated = true;
                }
                break;
            case 'void':
                $order->update_status('wc-cancelled');
                $order->add_order_note(sprintf(__('MultiSafepay payment status %s', 'multisafepay'), $status));

                $updated = true;
                break;
        }

        $return_url = $order->get_checkout_order_received_url();

        if ($redirect) {
            wp_redirect($return_url);
            exit();
        }

        header('Content-type: text/plain');
        $cancel_order = filter_input(INPUT_GET, 'cancel_order', FILTER_SANITIZE_STRING);
        if ($cancel_order) {
            if ($status == 'completed') {
                wp_redirect($return_url);
                exit();
            } else {
                $order->update_status('wc-cancelled');
                $location = wc_get_cart_url();
                wp_safe_redirect($location);
                exit();
            }
        }

        if ($cancel_order && ($status != 'completed')) {
            $order->update_status('wc-cancelled');
            $location = wc_get_cart_url();
            wp_safe_redirect($location);
            exit();
        }

        $getOrder = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_STRING);
        $getKey = filter_input(INPUT_GET, 'key', FILTER_SANITIZE_STRING);
        if ($getOrder || $getKey) {
            wp_safe_redirect($return_url);
            exit();
        }

        exit('OK');
    }

    public static function addFCO()
    {
        global $woocommerce;

        if (!empty($woocommerce->fco_added)) {
            return;
        }

        if (get_option('multisafepay_fco_enabled') == 'yes') {
            $woocommerce->fco_added = true;
            add_action('woocommerce_proceed_to_checkout', array(__CLASS__, 'getButtonFCO'), 12);
            add_action('woocommerce_review_order_after_submit', array(__CLASS__, 'getButtonFCO'), 12);
        }
    }

    public static function getButtonFCO()
    {

        if (get_woocommerce_currency() != 'EUR') {
            return;
        }

        // $button_locale_code = get_locale();
        // $image = plugins_url('/Images/' . $button_locale_code . '/button.png', __FILE__);
        $image = plugins_url('/Images/button.png', __FILE__);

        echo '<div id="msp_fastcheckout" >';
        echo '<a class="checkout-button"  style="width:219px;border:none;margin-bottom:15px;" href="' . add_query_arg('action', 'doFastCheckout', add_query_arg('wc-api', 'MultiSafepay_Gateways', home_url('/'))) . '">';
        echo "<img src='" . $image . "' style='border:none;vertical-align: center;width: 219px;border-radius: 0px;box-shadow: none;padding: 0px;' border='0' alt='" . __('Pay with Fastcheckout', 'multisafepay') . "'/>";
        echo '</a>';
        echo '</div>';
    }

    public static function doFastCheckout()
    {


        global $woocommerce;
        $msp = new Client();
        $helper = new Helper();
        $fco = new Fastcheckout();

        $msp->setApiKey($helper->getApiKey());
        $msp->setApiUrl($helper->getTestMode());

        $order_id = uniqid();
        $my_order = array(
                    'type' => 'checkout',
                    'order_id' => $order_id,
                    'currency' => get_woocommerce_currency(),
                    'amount' => round(WC()->cart->subtotal * 100),
                    'description' => 'Order #' . $order_id,
                    'var2' => $order_id,
                    'items' => $fco->setItemList($fco->getItemsFCO()),
                    'manual' => false,
                    'seconds_active' => $fco->getTimeActive(),
                    'payment_options' => array(
                        'notification_url' => $fco->getNurl(),
                        'redirect_url' => $fco->getNurl() . '&type=redirect',
                        'cancel_url' => wc_get_cart_url() . 'index.php?type=cancel&cancel_order=true',
                        'close_window' => true
                    ),
                    'google_analytics' => $fco->setGoogleAnalytics(),
                    'plugin' => $fco->setPlugin($woocommerce),
                    'gateway_info' => '',
                    'shopping_cart' => $fco->setCartFCO(),
                    'checkout_options' => $fco->setCheckoutOptionsFCO(),
        );

        try {
            $msg = null;
            $msp->orders->post($my_order);
            $url = $msp->orders->getPaymentLink();
        } catch (\Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            $helper->write_log('error: ' . $msg);
        }

        if ($msg) {
            $helper->write_log('MSP->transactiondata');
            $helper->write_log(print_r($my_order, true));
            $helper->write_log('MSP->End debug');

            if (strpos($msg, '1037') === 0) {
                $msg = __('There are no shipping methods available. Please double check your address, or contact us if you need any help.', 'multisafepay');
            }
            wc_add_notice($msg, 'error');
            wp_redirect(wc_get_checkout_url());
        } else {
            wp_redirect($url);
        }
    }
}
