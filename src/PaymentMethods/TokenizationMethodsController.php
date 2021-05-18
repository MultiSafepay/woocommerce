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

use MultiSafepay\Api\Tokens\Token;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Utils\Logger;
use WC_Payment_Token;
use WC_Payment_Token_CC;
use WC_Payment_Tokens;

class TokenizationMethodsController {

    /**
     * Filter that returns the tokens saved in MultiSafepay
     *
     * @param array  $tokens
     * @param int    $customer_id
     * @param string $gateway_id
     *
     * @return WC_Payment_Token_CC[]
     */
    public function multisafepay_get_customer_payment_tokens( array $tokens, int $customer_id, string $gateway_id ): array {
        if ( is_user_logged_in() && class_exists( 'WC_Payment_Token_CC' ) ) {
            $stored_tokens = array();
            foreach ( $tokens as $token ) {
                $stored_tokens[ $token->get_token() ] = $token;
            }

            // Account payment method page templates use wc_get_customer_saved_methods_list at the header
            // which generates a loop in combination with the method $token->save(), used in this filter: woocommerce_get_customer_payment_tokens
            if ( is_account_page() ) {
                remove_filter( 'woocommerce_get_customer_payment_tokens', array( $this, 'multisafepay_get_customer_payment_tokens' ), 10 );
            }

            if ( is_checkout() && 'multisafepay_creditcard' === $gateway_id || is_account_page() ) {
                $multisafepay_tokens = $this->get_multisafepay_tokens_by_customer_id();
                foreach ( $multisafepay_tokens as $multisafepay_token ) {
                    // If the token has not been registered
                    if ( ! isset( $stored_tokens[ $multisafepay_token->getToken() ] ) ) {
                        $token                      = $this->save_wc_payment_token_cc( $multisafepay_token, $customer_id );
                        $tokens[ $token->get_id() ] = $token;
                    }
                    // Since 4.1.0, the tokens has been saving without register the gateway_id
                    if ( isset( $stored_tokens[ $multisafepay_token->getToken() ] ) ) {
                        $token                      = $this->update_wc_payment_token_cc( $multisafepay_token, $stored_tokens[ $multisafepay_token->getToken() ]->get_id() );
                        $tokens[ $token->get_id() ] = $token;
                    }
                }
            }

            // Account payment method page calls wc_get_customer_saved_methods_list
            // Which generated a loop over the hook used in this filter: woocommerce_get_customer_payment_tokens
            if ( is_account_page() ) {
                add_filter( 'woocommerce_get_customer_payment_tokens', array( $this, 'multisafepay_get_customer_payment_tokens' ), 10, 3 );
            }
		}
        return $tokens;
    }

    /**
     * Returns a WC_Payment_Token_CC object for the given MultiSafepay Token object
     *
     * @see https://woocommerce.github.io/code-reference/classes/WC-Payment-Token-CC.html
     *
     * @param Token $multisafepay_token
     * @param int   $customer_id
     *
     * @return WC_Payment_Token_CC
     */
    private function save_wc_payment_token_cc( Token $multisafepay_token, int $customer_id ): WC_Payment_Token_CC {
        $token = new WC_Payment_Token_CC();
        $token->set_user_id( $customer_id );
        $token->set_token( $multisafepay_token->getToken() );
        $token->set_gateway_id( 'multisafepay_creditcard' );
        $token->set_card_type( $this->get_wc_payment_token_allowed_card_type( $multisafepay_token->getGatewayCode() ) );
        $token->set_last4( $multisafepay_token->getLastFour() );
        $token->set_expiry_month( $multisafepay_token->getExpiryMonth() );
        $token->set_expiry_year( '20' . $multisafepay_token->getExpiryYear() );
        $token->save();
        return $token;
    }

    /**
     * Returns an updated WC_Payment_Token_CC object with the given MultiSafepay Token object
     *
     * @see https://woocommerce.github.io/code-reference/classes/WC-Payment-Token-CC.html
     *
     * @param Token $multisafepay_token
     * @param int   $token_id
     *
     * @return WC_Payment_Token_CC
     */
    private function update_wc_payment_token_cc( Token $multisafepay_token, int $token_id ): WC_Payment_Token_CC {
        $token = WC_Payment_Tokens::get( $token_id );
        $token->set_gateway_id( 'multisafepay_creditcard' );
        $card_type = $this->get_wc_payment_token_allowed_card_type( $multisafepay_token->getGatewayCode() );
        if ( ! empty( $card_type ) ) {
            $token->set_card_type( $card_type );
        }
        $token->save();
        return $token;
    }

    /**
     * Return the allowed string to define the card_type of a WC_Payment_Token_CC object
     *
     * @see https://github.com/woocommerce/woocommerce/wiki/Payment-Token-API#set_card_type-type-
     *
     * @param string $multisafepay_token_gateway_code
     *
     * @return string
     */
    private function get_wc_payment_token_allowed_card_type( string $multisafepay_token_gateway_code ): string {
        $allowed_card_types_by_woocommerce = array(
            'MASTERCARD' => 'mastercard',
            'VISA'       => 'visa',
            'AMEX'       => 'american express',
        );

        if ( ! isset( $allowed_card_types_by_woocommerce[ $multisafepay_token_gateway_code ] ) ) {
            return '';
        }

        return $allowed_card_types_by_woocommerce[ $multisafepay_token_gateway_code ];
    }


    /**
     * Return MultiSafepay tokens by customer id
     *
     * @return Token[]
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    private function get_multisafepay_tokens_by_customer_id(): array {
        $sdk = new SdkService();
        try {
            $tokens = $sdk->get_sdk()->getTokenManager()->getList( (string) get_current_user_id() );
        } catch ( ApiException $api_exception ) {
            $tokens = array(); // No tokens where found while calling API, So return no tokens
        }
        return $tokens;
    }

    /**
     * Action triggered on set as default the given token in customer account
     *
     * @param int              $token_id
     * @param WC_Payment_Token $token
     *
     * @return void
     */
    public function woocommerce_payment_token_set_default( int $token_id, WC_Payment_Token $token ): void {
        $token->set_default( true );
    }

    /**
     * Action triggered on remove the select given in customer account
     *
     * @param int              $token_id
     * @param WC_Payment_Token $token
     *
     * @return void
     */
    public function woocommerce_payment_token_deleted( int $token_id, WC_Payment_Token $token ): void {
        $sdk = new SdkService();
        try {
            $remove = $sdk->get_sdk()->getTokenManager()->delete( $token->get_token(), (string) $token->get_user_id() );
        } catch ( ApiException $api_exception ) {
            Logger::log_error( $api_exception->getMessage() );
        }
    }

    /**
     * Filter the output of save_payment_method_checkbox method.
     *
     * @param string $html
     *
     * @return string
     */
    public function multisafepay_payment_gateway_save_new_payment_method_option_html( string $html ): string {
        $html = str_replace( 'Save to account', __( 'Save your credit card for the next purchase', 'multisafepay' ), $html );
        return $html;
    }

}
