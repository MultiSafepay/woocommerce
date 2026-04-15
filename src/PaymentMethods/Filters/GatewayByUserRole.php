<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Filters;

use MultiSafepay\WooCommerce\Utils\Logger;

/**
 * Filter payment gateways by user role.
 */
class GatewayByUserRole {

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger|null $logger
     */
    public function __construct( ?Logger $logger = null ) {
        $this->logger = $logger ?? new Logger();
    }

    /**
     * Filter the payment methods by user role defined in payment gateway settings.
     *
     * @param array $payment_gateways
     * @return array
     */
    public function filter_gateway_per_user_roles( array $payment_gateways ): array {
        $user_roles                 = is_user_logged_in() ? wp_get_current_user()->roles : array();
        $current_user_roles_for_log = ! empty( $user_roles ) ? implode( ', ', $user_roles ) : 'guest';

        foreach ( $payment_gateways as $gateway_id => $gateway ) {
            $gateway_settings   = is_object( $gateway ) && isset( $gateway->settings ) && is_array( $gateway->settings ) ? $gateway->settings : array();
            $allowed_user_roles = isset( $gateway_settings['user_roles'] ) ? wp_parse_list( $gateway_settings['user_roles'] ) : array();

            if ( ! empty( $allowed_user_roles ) && ! array_intersect( $user_roles, $allowed_user_roles ) ) {
                $this->logger->log_info( 'Payment method ' . $gateway_id . ' is being unset because the current user roles ' . $current_user_roles_for_log . ' are not allowed. Allowed roles: ' . implode( ', ', $allowed_user_roles ) );
                unset( $payment_gateways[ $gateway_id ] );
            }
        }

        return $payment_gateways;
    }
}
