<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

use WC_Cart;
use WC_Shipping_Rate;
use WC_Validation;

/**
 * Class QrCheckoutManager
 */
class QrCheckoutManager {

    /**
     * @var bool
     */
    public $is_validated = false;

    /**
     * @var array
     */
    public $customer_data = array(
        'billing'  => array(),
        'shipping' => array(),
    );

    /**
     * @var array
     */
    public $order_data = array();

    /**
     * @var array
     */
    public $posted_data = array();

    /**
     * Check if all mandatory fields are filled in the checkout to submit a MultiSafepay transaction
     * using Payment Component with QR code.
     *
     * @return bool
     */
    public function validate_checkout_fields(): bool {
        // Verify nonce
        if ( ! $this->verify_nonce() ) {
            $this->is_validated = false;
            return $this->is_validated;
        }

        // Get form data
        $this->posted_data = $this->get_posted_data();

        // Get required and extra fields
        $billing_required_fields = $this->get_required_fields();
        $billing_extra_fields    = $this->get_extra_fields();

        // Determine if shipping to a different address
        $ship_to_different_address = $this->is_shipping_to_different_address( $this->posted_data );

        // Get shipping fields if necessary
        $shipping_fields          = array();
        $shipping_required_fields = array();

        if ( $ship_to_different_address ) {
            $shipping_fields          = $this->get_shipping_fields( $billing_required_fields, $billing_extra_fields );
            $shipping_required_fields = $this->get_shipping_fields( $billing_required_fields, array() );
        }

        // Combine all fields
        $all_fields = array_merge( $billing_required_fields, $billing_extra_fields, $shipping_fields );

        // Combine all required fields
        $all_required_fields = array_merge( $billing_required_fields, $shipping_required_fields );

        // Get order fields
        $order_fields = $this->get_order_fields();

        // Process and validate fields
        $this->process_checkout_data( $all_fields, $all_required_fields, $order_fields );

        return $this->is_validated;
    }

    /**
     * Get the validated data after validation.
     *
     * @return array
     */
    public function get_checkout_data(): array {
        if ( ! $this->is_validated ) {
            $this->validate_checkout_fields();
        }

        return array(
            'customer' => $this->customer_data,
            'order'    => $this->order_data,
            'cart'     => $this->get_cart(),
            'shipping' => $this->get_shipping(),
            'coupons'  => $this->get_coupons(),
            'fees'     => $this->get_fees(),
            'other'    => $this->get_other(),
        );
    }

    /**
     * Get any other data available in the checkout.
     *
     * @return array
     */
    public function get_other(): array {
        $other_data = array();

        // Get the lists of fields we've already processed
        $already_processed = array_merge(
            $this->get_required_fields(),
            $this->get_extra_fields()
        );

        // Add shipping fields if needed
        if ( $this->is_shipping_to_different_address( $this->posted_data ) ) {
            $already_processed = array_merge(
                $already_processed,
                $this->get_shipping_fields( $this->get_required_fields(), $this->get_extra_fields() )
            );
        }

        // Look for any posted data not already captured
        foreach ( $this->posted_data as $key => $value ) {
            // Skip standard fields we've already defined and processed
            if ( ( strpos( $key, 'billing_' ) === 0 || strpos( $key, 'shipping_' ) === 0 ) &&
                in_array( $key, $already_processed, true ) ) {
                continue;
            }

            // Skip fields already in order_data
            if ( isset( $this->order_data[ $key ] ) ) {
                continue;
            }

            // Skip common WooCommerce fields that shouldn't be included
            $exclude_fields = array(
                'nonce',
                'form_data',
                '_wp_http_referer',
                'woocommerce-process-checkout-nonce',
                'ship_to_different_address',
                'payment_component_arguments_nonce',
            );

            if ( in_array( $key, $exclude_fields, true ) ) {
                continue;
            }

            // Add any remaining fields to other_data, including custom billing_* or shipping_* fields
            if ( is_array( $value ) ) {
                $other_data[ $key ] = array();
                foreach ( $value as $array_key => $array_value ) {
                    $other_data[ $key ][ $array_key ] = trim( wp_strip_all_tags( wp_unslash( $array_value ) ) );
                }
            } else {
                $other_data[ $key ] = trim( wp_strip_all_tags( wp_unslash( $value ) ) );
            }
        }

        return $other_data;
    }

    /**
     * Get the cart items.
     *
     * @return array
     */
    public function get_cart(): array {
        $cart_items = array();

        /** @var WC_Cart $cart */
        $cart = WC()->cart;

        $cart->calculate_totals();

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $cart_items[ $cart_item_key ] = array(
                'product_id'        => $cart_item['product_id'],
                'variation_id'      => $cart_item['variation_id'],
                'variation'         => $cart_item['variation'],
                'quantity'          => $cart_item['quantity'],
                'line_tax_data'     => $cart_item['line_tax_data'],
                'line_subtotal'     => $cart_item['line_subtotal'],
                'line_subtotal_tax' => $cart_item['line_subtotal_tax'],
                'line_tax'          => $cart_item['line_tax'],
                'line_total'        => $cart_item['line_total'],
                'data'              => $cart_item['data'],
            );
        }

        return $cart_items;
    }

    /**
     * Get the cart items.
     *
     * @return ?WC_Shipping_Rate
     */
    public function get_shipping(): ?WC_Shipping_Rate {
        /** @var WC_Cart $cart */
        $cart = WC()->cart;

        $cart->calculate_totals();

        if ( $cart->needs_shipping() ) {
            $shipping = $cart->get_shipping_methods()[0] ?? null;
        }

        return $shipping ?? null;
    }

    /**
     * Get the cart coupons.
     *
     * @return array
     */
    public function get_coupons(): array {
        $coupons = array();

        /** @var WC_Cart $cart */
        $cart = WC()->cart;

        $cart->calculate_totals();

        foreach ( $cart->get_coupons() as $coupon ) {
            $coupons[] = $coupon;
        }

        return $coupons;
    }

    /**
     * Get the cart fees.
     *
     * @return array
     */
    public function get_fees(): array {
        $fees = array();

        /** @var WC_Cart $cart */
        $cart = WC()->cart;

        $cart->calculate_totals();

        foreach ( $cart->get_fees() as $fee ) {
            $fees[] = $fee;
        }

        return $fees;
    }

    /**
     * Verify the nonce from the request.
     *
     * @return bool
     */
    public function verify_nonce(): bool {
        $payment_component_arguments_nonce = sanitize_key( $_POST['nonce'] ?? '' );
        return wp_verify_nonce( wp_unslash( $payment_component_arguments_nonce ), 'payment_component_arguments_nonce' ) !== false;
    }

    /**
     * Get the posted data from the form.
     *
     * @return array
     */
    public function get_posted_data(): array {
        $posted_data = array();

        if ( ! empty( $_POST['form_data'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $form_data = wp_unslash( $_POST['form_data'] );
            // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $form_data = wp_kses( $form_data, array() );
            $form_data = html_entity_decode( $form_data, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            parse_str( $form_data, $posted_data );
        }

        return $posted_data;
    }

    /**
     * Get the list of required fields.
     *
     * @return array
     */
    public function get_required_fields(): array {
        return array(
            'billing_first_name',
            'billing_last_name',
            'billing_address_1',
            'billing_city',
            'billing_postcode',
            'billing_country',
            'billing_email',
            'billing_phone',
        );
    }

    /**
     * Get the list of extra fields.
     *
     * @return array
     */
    public function get_extra_fields(): array {
        return array(
            'billing_company',
            'billing_address_2',
            'billing_state',
        );
    }

    /**
     * Get the list of order fields.
     *
     * @return array
     */
    public function get_order_fields(): array {
        return array(
            'payment_method',
            'shipping_method',
            'order_comments',
            'ip_address',
            'user_agent',
            'wc_order_attribution_source_type',
            'wc_order_attribution_referrer',
            'wc_order_attribution_utm_campaign',
            'wc_order_attribution_utm_source',
            'wc_order_attribution_utm_medium',
            'wc_order_attribution_utm_content',
            'wc_order_attribution_utm_id',
            'wc_order_attribution_utm_term',
            'wc_order_attribution_utm_source_platform',
            'wc_order_attribution_utm_creative_format',
            'wc_order_attribution_utm_marketing_tactic',
            'wc_order_attribution_session_entry',
            'wc_order_attribution_session_start_time',
            'wc_order_attribution_session_pages',
            'wc_order_attribution_session_count',
            'wc_order_attribution_user_agent',
        );
    }

    /**
     * Check if shipping to a different address.
     *
     * @param array $posted_data The posted form data.
     * @return bool
     */
    public function is_shipping_to_different_address( array $posted_data ): bool {
        return isset( $posted_data['ship_to_different_address'] ) &&
            filter_var( $posted_data['ship_to_different_address'], FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Get the shipping fields based on required and extra fields.
     *
     * @param array $billing_required_fields The required fields.
     * @param array $billing_extra_fields The extra fields.
     * @return array
     */
    public function get_shipping_fields( array $billing_required_fields, array $billing_extra_fields ): array {
        return array_map(
            static function( $field ) {
                return str_replace( 'billing_', 'shipping_', $field );
            },
            array_filter(
                array_merge( $billing_required_fields, $billing_extra_fields ),
                static function( $field ) {
                    // Exclude email and phone fields to be created as shipping fields.
                    return ! in_array( $field, array( 'billing_email', 'billing_phone' ), true );
                }
            )
        );
    }

    /**
     * Process customer and order fields from the posted data.
     *
     * @param array $all_fields All fields to check.
     * @param array $all_required_fields The required fields.
     * @param array $order_fields The order fields.
     */
    public function process_checkout_data( array $all_fields, array $all_required_fields, array $order_fields ): void {
        $this->is_validated = true;

        // Process customer fields (billing and shipping)
        foreach ( $all_fields as $field ) {
            if ( 'billing_email' === $field ) {
                $field_value = isset( $this->posted_data[ $field ] ) ? sanitize_email( wp_unslash( $this->posted_data[ $field ] ) ) : '';

                // Verify the email format using PHP's built-in filter validation
                if ( ! empty( $field_value ) && ! $this->validate_email( $field_value ) ) {
                    $this->is_validated = false;
                }
            } elseif ( strpos( $field, '_postcode' ) !== false ) {
                $field_value = isset( $this->posted_data[ $field ] ) ? wp_unslash( $this->posted_data[ $field ] ) : '';
                $field_value = trim( wp_strip_all_tags( $field_value ) );

                // Validate a postcode format if not empty
                if ( ! empty( $field_value ) ) {
                    $prefix  = strpos( $field, 'billing_' ) === 0 ? 'billing' : 'shipping';
                    $country = isset( $this->posted_data[ $prefix . '_country' ] ) ? wp_unslash( $this->posted_data[ $prefix . '_country' ] ) : '';
                    $country = trim( wp_strip_all_tags( $country ) );

                    if ( ! $this->validate_postcode( $field_value, $country ) ) {
                        $this->is_validated = false;
                    }
                }
            } else {
                $field_value = isset( $this->posted_data[ $field ] ) ? wp_unslash( $this->posted_data[ $field ] ) : '';
                $field_value = trim( wp_strip_all_tags( $field_value ) );
            }

            // Check if the required field is empty
            if ( empty( $field_value ) && in_array( $field, $all_required_fields, true ) ) {
                $this->is_validated = false;
            }

            // Organize data into customer billing or shipping
            if ( strpos( $field, 'billing_' ) === 0 ) {
                $field_key                                    = str_replace( 'billing_', '', $field );
                $this->customer_data['billing'][ $field_key ] = $field_value;
            } elseif ( strpos( $field, 'shipping_' ) === 0 ) {
                $field_key                                     = str_replace( 'shipping_', '', $field );
                $this->customer_data['shipping'][ $field_key ] = $field_value;
            }
        }

        // Process order fields
        foreach ( $order_fields as $field ) {
            // Special handling for ip_address
            if ( 'ip_address' === $field ) {
                $this->order_data['ip_address'] = ( new QrOrder() )->get_customer_ip_address() ?? '';
                continue;
            }
            // Special handling for user_agent
            if ( 'user_agent' === $field ) {
                $this->order_data['user_agent'] = ( new QrOrder() )->get_user_agent() ?? '';
                continue;
            }
            // Process direct fields
            if ( isset( $this->posted_data[ $field ] ) && ! is_array( $this->posted_data[ $field ] ) ) {
                $this->order_data[ $field ] = trim( wp_strip_all_tags( wp_unslash( $this->posted_data[ $field ] ) ) );
            } elseif ( isset( $this->posted_data[ $field ] ) && is_array( $this->posted_data[ $field ] ) ) {
                $this->order_data[ $field ] = array();
                foreach ( $this->posted_data[ $field ] as $key => $value ) {
                    $this->order_data[ $field ][ $key ] = trim( wp_strip_all_tags( wp_unslash( $value ) ) );
                }
            }
        }

        // Process any additional fields from posted_data
        foreach ( $this->posted_data as $key => $value ) {
            // Skip already processed customer fields
            if ( strpos( $key, 'billing_' ) === 0 || strpos( $key, 'shipping_' ) === 0 ) {
                continue;
            }

            // Skip already processed order fields
            if ( isset( $this->order_data[ $key ] ) ) {
                continue;
            }

            // Add any additional relevant fields to order data
            if ( in_array( $key, $order_fields, true ) ) {
                if ( is_array( $value ) ) {
                    $this->order_data[ $key ] = array();
                    foreach ( $value as $array_key => $array_value ) {
                        $this->order_data[ $key ][ $array_key ] = trim( wp_strip_all_tags( wp_unslash( $array_value ) ) );
                    }
                } else {
                    $this->order_data[ $key ] = trim( wp_strip_all_tags( wp_unslash( $value ) ) );
                }
            }
        }
    }

    /**
     * Validate the email address format
     *
     * @param string $email The email to validate
     * @return bool Whether the email is valid
     */
    private function validate_email( string $email ): bool {
        return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
    }

    /**
     * Validate a postcode format using WooCommerce's validation
     *
     * @param string $postcode The postcode to validate
     * @param string $country The country code
     * @return bool Whether the postcode is valid
     */
    private function validate_postcode( string $postcode, string $country ): bool {
        if ( ! WC_Validation::is_postcode( $postcode, $country ) ) {
            return false;
        }

        return true;
    }
}
