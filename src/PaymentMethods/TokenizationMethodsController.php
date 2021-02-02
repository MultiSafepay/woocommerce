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

use MultiSafepay\Exception\ApiException;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\Api\Tokens\Token;
use WC_Payment_Token;
use WC_Payment_Token_CC;

class TokenizationMethodsController {

    /**
     * Filter that returns the tokens saved in MultiSafepay
     *
     * @param array  $tokens
     * @param int    $customer_id
     * @param string $gateway_id
     *
     * @return WC_Payment_Token_CC[]
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function multisafepay_get_customer_payment_tokens( array $tokens, int $customer_id, string $gateway_id ): array {
        if ( is_user_logged_in() && class_exists( 'WC_Payment_Token_CC' ) ) {
            $stored_tokens = array();
            foreach ( $tokens as $token ) {
                $stored_tokens[] = $token->get_token();
            }
            $multisafepay_tokens = $this->get_multisafepay_tokens_by_customer_id();
            foreach ( $multisafepay_tokens as $multisafepay_token ) {
                if ( ! in_array( $multisafepay_token->getToken(), $stored_tokens, true ) ) {
                    $wc_payment_token_cc                      = $this->create_wc_payment_token_cc( $multisafepay_token, $gateway_id );
                    $tokens[ $wc_payment_token_cc->get_id() ] = $wc_payment_token_cc;
                }
            }
            return $tokens;
        }
    }

    /**
     * Returns a WC_Payment_Token_CC object for the given MultiSafepay Token object
     *
     * @see https://woocommerce.github.io/code-reference/classes/WC-Payment-Token-CC.html
     *
     * @param Token  $multisafepay_token
     * @param string $gateway_id
     *
     * @return WC_Payment_Token_CC
     */
    private function create_wc_payment_token_cc( Token $multisafepay_token, string $gateway_id ): WC_Payment_Token_CC {
        $token = new WC_Payment_Token_CC();
        $token->set_user_id( get_current_user_id() );
        $token->set_token( $multisafepay_token->getToken() );
        $token->set_gateway_id( $gateway_id );
        $token->set_card_type( strtolower( $multisafepay_token->getGatewayCode() ) );
        $token->set_last4( $multisafepay_token->getLastFour() );
        $token->set_expiry_month( $multisafepay_token->getExpiryMonth() );
        $token->set_expiry_year( '20' . $multisafepay_token->getExpiryYear() );
        $token->save();
        return $token;
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
            $token->delete( true );
        } catch ( ApiException $api_exception ) {
            if ( get_option( 'multisafepay_debugmode', false ) ) {
                $logger = wc_get_logger();
                $logger->log( 'warning', $api_exception->getMessage() );
            }
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
