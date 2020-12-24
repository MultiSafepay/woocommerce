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

/**
 * Define the Gateways.
 *
 * @since   4.0.0
 */
class Gateways {

    const GATEWAYS = array(
        'multisafepay_multisafepay'      => MultiSafepay::class,
        'multisafepay_afterpay'          => Afterpay::class,
        'multisafepay_alipay'            => Alipay::class,
        'multisafepay_amex'              => Amex::class,
        'multisafepay_applepay'          => ApplePay::class,
        'multisafepay_bancontact'        => Bancontact::class,
        'multisafepay_banktrans'         => BankTrans::class,
        'multisafepay_belfius'           => Belfius::class,
        'multisafepay_cbc'               => Cbc::class,
        'multisafepay_creditcard'        => CreditCard::class,
        'multisafepay_dbrtp'             => Dbrtp::class,
        'multisafepay_dirdeb'            => Dirdeb::class,
        'multisafepay_dotpay'            => Dotpay::class,
        'multisafepay_einvocing'         => Einvocing::class,
        'multisafepay_eps'               => Eps::class,
        'multisafepay_giropay'           => Giropay::class,
        'multisafepay_ideal'             => Ideal::class,
        'multisafepay_idealqr'           => IdealQr::class,
        'multisafepay_in3'               => In3::class,
        'multisafepay_inghome'           => IngHomePay::class,
        'multisafepay_kbc'               => Kbc::class,
        'multisafepay_klarna'            => Klarna::class,
        'multisafepay_maestro'           => Maestro::class,
        'multisafepay_mastercard'        => MasterCard::class,
        'multisafepay_payafter'          => PayAfterDelivery::class,
        'multisafepay_paypal'            => PayPal::class,
        'multisafepay_paysafecard'       => Paysafecard::class,
        'multisafepay_santander'         => Santander::class,
        'multisafepay_directbank'        => Sofort::class,
        'multisafepay_trustly'           => Trustly::class,
        'multisafepay_visa'              => Visa::class,

        'multisafepay_babycadeaubon'     => Babycadeaubon::class,
        'multisafepay_beautywellness'    => Beautywellness::class,
        'multisafepay_boekenbon'         => Boekenbon::class,
        'multisafepay_fashioncheque'     => Fashioncheque::class,
        'multisafepay_fashiongiftcard'   => Fashiongiftcard::class,
        'multisafepay_fietsenbon'        => Fietsenbon::class,
        'multisafepay_gezondheidsbon'    => Gezondheidsbon::class,
        'multisafepay_givacard'          => Givacard::class,
        'multisafepay_good4fun'          => Good4fun::class,
        'multisafepay_good4card'         => Goodcard::class,
        'multisafepay_nationaletuinbon'  => Nationaletuinbon::class,
        'multisafepay_parfumcadeaukaart' => Parfumcadeaukaart::class,
        'multisafepay_podium'            => Podium::class,
        'multisafepay_sportenfit'        => Sportenfit::class,
        'multisafepay_vvvcadeaukaart'    => Vvvcadeaukaart::class,
        'multisafepay_webshopgiftcard'   => Webshopgiftcard::class,
        'multisafepay_wellnessgiftcard'  => Wellnessgiftcard::class,
        'multisafepay_wijncadeau'        => Wijncadeau::class,
        'multisafepay_winkelcheque'      => Winkelcheque::class,
        'multisafepay_yourgift'          => Yourgift::class,
    );

    /**
     * Return an array with all MultiSafepay gateways ids
     *
     * @return array
     */
    public static function get_gateways_ids(): array {
        $gateways_ids = array();
        foreach ( self::GATEWAYS as $gateway_id => $gateway ) {
            $gateways_ids[] = $gateway_id;
        }
        return $gateways_ids;
    }

    /**
     * Return the payment method code needed by WooCommerce
     *
     * @param string $code
     * @return array
     */
    public static function get_payment_method_id_by_gateway_code( string $code ): string {
        foreach ( self::GATEWAYS as $gateway ) {
            $gateway = new $gateway();
            if ( $gateway->get_payment_method_code() === $code ) {
                return $gateway->get_payment_method_id();
            }
        }
    }

    /**
     * Return the payment method title needed by WooCommerce
     *
     * @param string $code
     * @return string
     */
    public static function get_payment_method_name_by_gateway_code( string $code ): string {
        foreach ( self::GATEWAYS as $gateway ) {
            $gateway = new $gateway();
            if ( $gateway->get_payment_method_code() === $code ) {
                return $gateway->get_payment_method_title();
            }
        }
    }

    /**
     * Return the gateway code for the given gateway_id
     *
     * @param string $gateway_id
     * @return string
     */
    public static function get_gateway_code_by_gateway_id( string $gateway_id ): string {
        $gateway = self::GATEWAYS[ $gateway_id ];
        return ( new $gateway() )->get_payment_method_code();
    }

}
