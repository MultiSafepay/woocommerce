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
 * @author      TechSupport <techsupport@multisafepay.com>
 * @copyright   Copyright (c) 2017 MultiSafepay, Inc. (http://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
class MultiSafepay_Gateways
{

    public static function register()
    {
        update_option('multisafepay_version', '3.2.0', 'yes');

        add_filter('woocommerce_payment_gateways',              array(__CLASS__, '_getGateways'));
        add_filter('woocommerce_payment_gateways_settings',     array(__CLASS__, '_addGlobalSettings'), 1);

        add_action('wp_loaded',                                 array(__CLASS__, 'MultiSafepay_Response'));
        add_action('init',                                      array(__CLASS__, 'addFCO'));
        add_action('woocommerce_api_' . strtolower(get_class()),array(__CLASS__, 'doFastCheckout'));

        add_action('woocommerce_payment_complete',        array(__CLASS__, 'getRealPaymentMethod'), 10, 1);

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

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $woocommerce_tables = "CREATE TABLE {$wpdb->prefix}woocommerce_multisafepay
                                (   id bigint(20) NOT NULL auto_increment,
                                    trixid varchar(200) NOT NULL,
                                    orderid varchar(200) NOT NULL,
                                    status varchar(200) NOT NULL,
                                    PRIMARY KEY  (id)
                                ) $collate;";
        dbDelta($woocommerce_tables);
    }


   public static function getRealPaymentMethod() {
        global $wpdb;

        $trns_id  = filter_input(INPUT_GET, 'transactionid',  FILTER_SANITIZE_STRING);

        if ( empty ($trns_id )) {
            return;
        }

        // Get real payment method
        //
        $msp    = new MultiSafepay_Client();
        $helper = new MultiSafepay_Helper_Helper();

        $msp->setApiKey($helper->getApiKey());
        $msp->setApiUrl($helper->getTestMode());

        try {
            $msg = null;
            $transactie = $msp->orders->get($trns_id, 'orders', array(), false);
        } catch (Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            $helper->write_log($msg);
            return;
        }

        if ($transactie->fastcheckout == 'NO' &&  isset ($transactie->var2)) {
            $order_id = $transactie->var2;
        }else{
            $tablename = $wpdb->prefix . 'woocommerce_multisafepay';
            $sql = $wpdb->prepare("SELECT orderid FROM {$tablename} WHERE trixid = %s", $trns_id);
            $order_id = $wpdb->get_var($sql);
        }
        $order   = wc_get_order($order_id);


        $payment_method       = false;
        $payment_method_title = false;
        $gateway              = $transactie->payment_details->type;

        $tablename = $wpdb->prefix . 'options';
        $results   = $wpdb->get_results("SELECT option_name, option_value FROM {$tablename} WHERE `option_name` like 'woocommerce_multisafepay_%'");

        foreach( $results as $result)
        {
            $options = get_option( $result->option_name, array());

            if ( isset ($options['gateway'])  && ( $gateway == $options['gateway']) )
            {
                preg_match('/woocommerce_(.*)_settings/', $result->option_name, $matches);
                $payment_method         = $matches[1];
                $payment_method_title   = $options['title'];
                break;
            }
        }

        // Initial payment method differens from real payment method.
        if ($payment_method != false && get_post_meta( $order_id, '_payment_method', true ) != $payment_method) {

            $order->add_order_note( sprintf(__('Payment started with %s, but finally paid by %s', 'multisafepay'), get_post_meta( $order_id, '_payment_method_title', true ), $payment_method_title));

            update_post_meta( $order_id, '_payment_method',        $payment_method);
            update_post_meta( $order_id, '_payment_method_title',  $payment_method_title );
        }
    }



    public static function _getGateways($arrDefault)
    {
        $paymentOptions = array(
              'MultiSafepay_Gateway_Afterpay'
            , 'MultiSafepay_Gateway_Alipay'
            , 'MultiSafepay_Gateway_Amex'
            , 'MultiSafepay_Gateway_Bancontact'
            , 'MultiSafepay_Gateway_Banktrans'
            , 'MultiSafepay_Gateway_Belfius'
            , 'MultiSafepay_Gateway_Creditcard'
            , 'MultiSafepay_Gateway_Dirdeb'
            , 'MultiSafepay_Gateway_Dotpay'
            , 'MultiSafepay_Gateway_Einvoice'
            , 'MultiSafepay_Gateway_Eps'
            , 'MultiSafepay_Gateway_Ferbuy'
            , 'MultiSafepay_Gateway_Giropay'
            , 'MultiSafepay_Gateway_Ideal'
            , 'MultiSafepay_Gateway_Ing'
            , 'MultiSafepay_Gateway_Kbc'
            , 'MultiSafepay_Gateway_Klarna'
            , 'MultiSafepay_Gateway_Maestro'
            , 'MultiSafepay_Gateway_Mastercard'
            , 'MultiSafepay_Gateway_Payafter'
            , 'MultiSafepay_Gateway_Paypal'
            , 'MultiSafepay_Gateway_Paysafecard'
            , 'MultiSafepay_Gateway_Santander'
            , 'MultiSafepay_Gateway_Sofort'
            , 'MultiSafepay_Gateway_Trustly'
            , 'MultiSafepay_Gateway_Trustpay'
            , 'MultiSafepay_Gateway_Visa');

        $giftCards = array(
            'MultiSafepay_Gateway_Beautyandwellness'
            , 'MultiSafepay_Gateway_Nationalebioscoopbon'
            , 'MultiSafepay_Gateway_Boekenbon'
            , 'MultiSafepay_Gateway_Erotiekbon'
            , 'MultiSafepay_Gateway_Fashioncheque'
            , 'MultiSafepay_Gateway_Fashiongiftcard'
            , 'MultiSafepay_Gateway_Fietsbon'
            , 'MultiSafepay_Gateway_Fijncadeau'
            , 'MultiSafepay_Gateway_Gezondheidsbon'
            , 'MultiSafepay_Gateway_Givacard'
            , 'MultiSafepay_Gateway_Goodcard'
            , 'MultiSafepay_Gateway_Liefcadeaukaart'
            , 'MultiSafepay_Gateway_Nationaletuinbon'
            , 'MultiSafepay_Gateway_Parfumcadeaukaart'
            , 'MultiSafepay_Gateway_Podiumcadeaukaart'
            , 'MultiSafepay_Gateway_Sportenfit'
            , 'MultiSafepay_Gateway_VVVBon'
            , 'MultiSafepay_Gateway_Webshopgiftcard'
            , 'MultiSafepay_Gateway_Wellnessgiftcard'
            , 'MultiSafepay_Gateway_Wijncadeau'
            , 'MultiSafepay_Gateway_Winkelcheque'
            , 'MultiSafepay_Gateway_Yourgift');


        $giftcards_enabled = get_option("multisafepay_giftcards_enabled") == 'yes' ? true : false;
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
            'desc'      => '<p>' . __('The following options are needed to make use of the MultiSafepay plug-in', 'multisafepay') . '</p>',
            'id'        => 'multisafepay_general_settings'
        );
        $addedSettings[] = array(
            'name'      => __('API key', 'multisafepay'),
            'type'      => 'text',
            'desc_tip'  => __('Copy the API-Key from your MultiSafepay account', 'multisafepay'),
            'id'        => 'multisafepay_api_key',
            'css'       => 'min-width:350px;',
        );
        $addedSettings[] = array(
            'name'      => __('Test Mode', 'multisafepay'),
            'desc'      => sprintf(__('Activate %s', 'multisafepay'), __('Test Mode', 'multisafepay')),
            'type'      => 'checkbox',
            'default'   => 'yes',
            'desc_tip'  => __('Only enable if the API-Key is from a MultiSafepay Test-account.', 'multisafepay'),
            'id'        => 'multisafepay_testmode'
        );

        $addedSettings[] = array(
            'name'      => __('FastCheckout', 'multisafepay'),
            'desc'      => sprintf(__('Activate %s', 'multisafepay'), __('FastCheckout', 'multisafepay')),
            'type'      => 'checkbox',
            'default'   => 'no',
            'desc_tip'  => sprintf(__('When enabled %s will be available during checkout.', 'multisafepay'), __('FastCheckout', 'multisafepay')),
            'id'        => 'multisafepay_fco_enabled'
        );

        $addedSettings[] = array(
            'name'      => __('GiftCards', 'multisafepay'),
            'desc'      => sprintf(__('Activate %s', 'multisafepay'), __('GiftCards', 'multisafepay')),
            'type'      => 'checkbox',
            'default'   => 'no',
            'desc_tip'  => sprintf(__('When enabled %s will be available during checkout.', 'multisafepay'), __('GiftCards', 'multisafepay')),
            'id'        => 'multisafepay_giftcards_enabled'
        );

        $addedSettings[] = array(
            'name'      => __('Expire order', 'multisafepay'),
            'type'      => 'number',
            'default'   => 30,
            'desc_tip'  => __('Time before unfinished order is set to expired', 'multisafepay'),
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
            'name'      => __('Invoice', 'multisafepay'),
            'desc'      => __('Send Invoice', 'multisafepay'),
            'type'      => 'checkbox',
            'default'   => 'yes',
            'desc_tip'  => __('When enabled an invoice is send after a transaction is completed', 'multisafepay'),
            'id'        => 'multisafepay_send_invoice',
        );

        $addedSettings[] = array(
            'name'      => __('Analytics', 'multisafepay'),
            'desc'      => __('Google Analytics', 'multisafepay'),
            'type'      => 'text',
            'desc_tip'  => __('Your Google Analytics tracking code', 'multisafepay'),
            'id'        => 'multisafepay_ga',
        );

        $addedSettings[] = array(
            'name'      => __('Debug', 'multisafepay'),
            'desc'      => __('Activate debug mode', 'multisafepay'),
            'type'      => 'checkbox',
            'default'   => 'no',
            'desc_tip'  => __('When enabled (and wordpress debug is enabled it will log transactions)', 'multisafepay'),
            'id'        => 'multisafepay_debugmode',
        );
        $addedSettings[] = array(
            'name'      => __('Notification-URL', 'multisafepay'),
            'type'      => 'text',
            'default'   => sprintf('%s/?page=multisafepaynotify', site_url()),
            'desc'      => __('Copy&Paste this URL to your website configuration Notification-URL at your Multisafepay dashboard.', 'multisafepay'),
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

    public static function Multisafepay_Response()
    {

        $type           = filter_input(INPUT_GET, 'type',           FILTER_SANITIZE_STRING);
        $trns_id        = filter_input(INPUT_GET, 'transactionid',  FILTER_SANITIZE_STRING);
        $identifier     = filter_input(INPUT_GET, 'identifier',     FILTER_SANITIZE_STRING);
        $cancel_order   = filter_input(INPUT_GET, 'cancel_order',   FILTER_SANITIZE_STRING);

        if (empty($trns_id) && empty($identifier)) {
            return;
        }

        global $wpdb, $woocommerce;
        $helper = new MultiSafepay_Helper_Helper();

        $redirect        = false;
        $initial_request = false;

        switch ($type) {
            case 'initial':
                $initial_request = true;
                break;
            case 'redirect':
                $redirect = true;
                break;
            case 'cancel':
                return true;
            case 'shipping':
                $fco = new MultiSafepay_Gateway_Fastcheckout();
                print_r ($fco->get_shipping_methods_xml());
                exit;
/*
            case 'feeds':
                require_once dirname(__FILE__) . '/Helper/Feeds.php';
                return true;
                break;
*/
            default:
                break;
        }


        // If no transaction-id there is nothing to process..
        if (empty($trns_id)) {
            return;
        }

        $msp = new MultiSafepay_Client();
        $helper = new MultiSafepay_Helper_Helper();

        $msp->setApiKey($helper->getApiKey());
        $msp->setApiUrl($helper->getTestMode());

        try {
            $msg = null;
            $transactie = $msp->orders->get($trns_id, 'orders', array(), false);
        } catch (Exception $e) {

            $msg = htmlspecialchars($e->getMessage());
            $helper->write_log($msg);
            return;
        }

        $updated  = false;
        $status   = $transactie->status;


        if ($transactie->fastcheckout == 'NO' &&  isset ($transactie->var2)) {
            $order_id = $transactie->var2;
        }else{
            $tablename = $wpdb->prefix . 'woocommerce_multisafepay';
            $sql = $wpdb->prepare("SELECT orderid FROM {$tablename} WHERE trixid = %s", $trns_id);
            $order_id = $wpdb->get_var($sql);
        }
        $order   = wc_get_order($order_id);



        if ($cancel_order && ($status != 'completed')) {
            $order->update_status('wc-cancelled');
            $location = wc_get_cart_url();
            wp_safe_redirect($location);
            exit();
        }

        $amount     = $transactie->amount / 100;
        $gateway    = $transactie->payment_details->type;

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
                $order_id     = (method_exists($order,'get_id'))     ? $order->get_id()      : $order->id;

                $wpdb->query("INSERT INTO " . $wpdb->prefix . 'woocommerce_multisafepay' . " (trixid, orderid, status) VALUES ('" . $trns_id . "', '" . $order_id . "', '" . $status . "'  )");

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
                        $shipping['method_title'] = $transactie->order_adjustment->shipping->flat_rate_shipping->name;
                        $shipping['total'] = $transactie->order_adjustment->shipping->flat_rate_shipping->cost;

                        $rate = new WC_Shipping_Rate($shipping_method->id, isset($shipping['method_title']) ? $shipping['method_title'] : '', isset($shipping['total']) ? floatval($shipping['total']) : 0, array(), $shipping_method->id);
                        $order->add_shipping($rate);
                        break;
                    }
                }

                $order->add_order_note($transactie->transaction_id);

                // Add payment method
                $gateways = new WC_Payment_Gateways();
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
                        $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku->sku));
                    } elseif (!empty($sku->id)) {
                        $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE post_id='%s' LIMIT 1", $sku->id));
                    }

                    if ($product_id) {
                        $product_item = new WC_Product($product_id);
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
                        $new_order_tax = round($order_data['_order_tax'][0] - (($unit_price * (1 + $tax_percentage)) - $unit_price), 2);
                        update_post_meta($order_id, '_order_tax', $new_order_tax);
                        $order->add_coupon($code, $unit_price, $applied_discount_tax);
                    }

/*
                    // Ordercoupon
                    $applied_discount_tax = 0;
                    if (!empty($sku->ordercoupon)) {
                        $code = $sku->ordercoupon;
                        $amount = (float) str_replace('-', '', $product['unit_price']);
                        update_post_meta($order_id, '_cart_discount', $amount);
                        update_post_meta($order_id, '_order_total', $details['transaction']['amount'] / 100);
                        $tax_percentage = (($details['transaction']['amount'] / 100) - ($details['order-total']['total'] - $details['total-tax']['total'] + $details['shipping']['cost'])) / ($details['order-total']['total'] - $details['total-tax']['total'] + $details['shipping']['cost']);
                        $applied_discount_tax = round(($amount * (1 + $tax_percentage)) - $amount, 2);
                        update_post_meta($order_id, '_cart_discount_tax', $applied_discount_tax);
                        $order->calculate_taxes();
                        $order_data = get_post_meta($order_id);
                        $new_order_tax = round($order_data['_order_tax'][0] - (($amount * (1 + $tax_percentage)) - $amount), 2);
                        update_post_meta($order_id, '_order_tax', $new_order_tax);
                        $id = $order->add_coupon($code, $amount, $applied_discount_tax);
                    }

                    // Cart Fee
                    if (!empty($sku->fee)) {
                        //TODO PROCESS CART FEE
                    }
*/
                }


                update_post_meta($order_id, '_order_total', $transactie->amount / 100);
                $order->calculate_taxes();

                foreach ($order->get_items('tax') as $key => $value) {
                    $data = wc_get_order_item_meta($key, 'tax_amount');
                    wc_update_order_item_meta($key, 'tax_amount', $data - $applied_discount_tax);
                }
            }

        }


        $orderStatus = $order->get_status();

        switch ($status) {
            case 'cancelled':
                $order->update_status('wc-cancelled');
                $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                $updated = true;
                break;
            case 'initialized':
                if ($gateway == 'BANKTRANS') {
                    $order->update_status('wc-on-hold', sprintf(__('Banktransfer payment. Waiting for payment update', 'multisafepay'), $amount));
                    $return_url = $order->get_checkout_order_received_url();
                    $updated = true;
                    break;
                } else {
                    $order->update_status('wc-pending');
                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                    $updated = true;
                    break;
                }
            case 'completed':
                if ($order->get_total() != $amount) {
                    if ($orderStatus != 'processing') {
                        $order->update_status('wc-on-hold', sprintf(__('Validation error: Multisafepay amounts do not match (gross %s).', 'multisafepay'), $amount));
                    }
                }

                if ($orderStatus != 'processing' && $orderStatus != 'completed' && $orderStatus != 'wc-completed') {
                    $order->payment_complete();
                    $woocommerce->cart->empty_cart();
                } else {
                    $updated = true;
                }
                if ($status == 'completed' && $gateway == 'KLARNA') {
                    $order->add_order_note(__('Klarna Reservation number: ', 'multisafepay') . $transactie->payment_details->external_transaction_id);
                }

                break;
            case 'refunded':
                if ($order->get_total() == $amount) {
                    $order->update_status('wc-refunded', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                }
                $updated = true;
                break;
            case 'uncleared':

                if ($orderStatus == 'on-hold') {
                    break;
                }

                $order->update_status('wc-on-hold');
                $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                $updated = true;
                break;
            case 'reserved':
            case 'declined':
            case 'expired':
                // Only change the orderstatus if the current status is pending or on-hold
                if ( $orderStatus == 'pending' ||  $orderStatus == 'on-hold') {
                    $order->update_status('wc-failed', sprintf(__('Payment %s via Multisafepay.', 'multisafepay'), strtolower($status)));
                    $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));
                    $updated = true;
                }
                break;
            case 'void' :
                $order->update_status('wc-cancelled');
                $order->add_order_note(sprintf(__('Multisafepay payment status %s', 'multisafepay'), $status));

                $updated = true;
                break;
            case 'shipped' :
                if ($gateway == 'KLARNA') {

                    $settings = (array) get_option("woocommerce_multisafepay_klarna_settings");
                    $klarna_eid    = $settings['eid'];
                    $klarna_secret = $settings['secret'];
                    if ($klarna_eid && $klarna_secret) {

                        $invoice_nr    = $transactie->payment_details->external_transaction_id;

                        $secretParts = array($klarna_eid, $invoice_nr, $klarna_secret);
                        $secret = urlencode(base64_encode(hash('sha512', implode(':', $secretParts), true)));

                        $url = 'https://online.klarna.com/invoices/'.  $invoice_nr . '.pdf?secret='. $secret;

                        $order->add_order_note(__('Klarna Invoice: ') . '<br /><a href=' . $url . '>' . $url . '</a>');
                    }else{
                        $order->add_order_note(__('Klarna Invoice: ') . __('not available.'));
                    }
                }
                break;
        }

        $return_url = $order->get_checkout_order_received_url();

        if ($redirect) {
            wp_redirect($return_url);
            exit();
        }
        if ($initial_request) {
            return;
        }

        header("Content-type: text/plain");
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

        if (get_option('multisafepay_fco_enabled') == "yes") {
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

//        $button_locale_code = get_locale();
//        $image = plugins_url('/Images/' . $button_locale_code . '/button.png', __FILE__);
        $image = plugins_url('/Images/button.png', __FILE__);

        echo '<div id="msp_fastcheckout" >';
        echo '<a class="checkout-button"  style="width:219px;border:none;margin-bottom:15px;" href="' . add_query_arg('action', 'doFastCheckout', add_query_arg('wc-api', 'MultiSafepay_Gateways', home_url('/'))) . '">';
        echo "<img src='" . $image . "' style='border:none;vertical-align: center;width: 219px;border-radius: 0px;box-shadow: none;padding: 0px;' border='0' alt='" . __('Pay with FastCheckout', 'multisafepay') . "'/>";
        echo "</a>";
        echo '</div>';
    }

    public static function doFastCheckout()
    {


        global $woocommerce;
        $msp = new MultiSafepay_Client();
        $helper = new MultiSafepay_Helper_Helper();
        $fco = new MultiSafepay_Gateway_Fastcheckout();

        $msp->setApiKey($helper->getApiKey());
        $msp->setApiUrl($helper->getTestMode());

        $order_id = uniqid();
        $my_order = array(
                    "type" => 'checkout',
                    "order_id" => $order_id,
                    "currency" => get_woocommerce_currency(),
                    "amount" => round(WC()->cart->subtotal * 100),
                    "description" => 'Order #' . $order_id,
                    "var2" => $order_id,
                    "items" => $fco->setItemList($fco->getItemsFCO()),
                    "manual" => false,
                    "seconds_active" => $fco->getTimeActive(),
                    "payment_options" => array(
                        "notification_url" => $fco->getNurl() . '&type=initial',
                        "redirect_url" => $fco->getNurl() . '&type=redirect',
                        "cancel_url" => wc_get_cart_url() . 'index.php?type=cancel&cancel_order=true',
                        "close_window" => true
                    ),
                    "google_analytics" => $fco->setGoogleAnalytics(),
                    "plugin" => $fco->setPlugin($woocommerce),
                    "gateway_info" => '',
                    "shopping_cart" => $fco->setCartFCO(),
                    "checkout_options" => $fco->setCheckoutOptionsFCO(),
        );

        try {
            $msg = null;
            $msp->orders->post($my_order);
            $url = $msp->orders->getPaymentLink();
        } catch (Exception $e) {

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
