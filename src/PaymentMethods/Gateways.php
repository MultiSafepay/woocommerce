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

/**
 * Define the Gateways.
 *
 * @since   4.0.0
 */
class Gateways {

    const GATEWAYS = array(
        'multisafepay'  => MultiSafepay::class,
        'afterpay'      => Afterpay::class,
        'alipay'        => Alipay::class,
        'amex'          => Amex::class,
        'applepay'      => ApplePay::class,
        'bancontact'    => Bancontact::class,
        'banktrans'     => BankTrans::class,
        'belfius'       => Belfius::class,
        'cbc'           => Cbc::class,
        'creditcard'    => CreditCard::class,
        'dbrtp'         => Dbrtp::class,
        'dirdeb'        => Dirdeb::class,
        'dotpay'        => Dotpay::class,
        'einvocing'     => Einvocing::class,
        'eps'           => Eps::class,
        'giropay'       => Giropay::class,
        'ideal'         => Ideal::class,
        'idealqr'       => IdealQr::class,
        'in3'           => In3::class,
        'inghome'       => IngHomePay::class,
        'kbc'           => Kbc::class,
        'klarna'        => Klarna::class,
        'maestro'       => Maestro::class,
        'mastercard'    => MasterCard::class,
        'payafter'      => PayAfterDelivery::class,
        'paypal'        => PayPal::class,
        'paysafecard'   => Paysafecard::class,
        'santander'     => Santander::class,
        'directbank'    => Sofort::class,
        'trustly'       => Trustly::class,
        'visa'          => Visa::class,
    );

    const GIFTCARDS = array(
        'babycadeaubon'  => Babycadeaubon::class,
    );


    /**
     * Return an array with all Payment methods
     * @return array|string[]
     */
    public static function get_payment_methods (): array {
        if (get_option('multisafepay_giftcards_enabled') === 'no') {
            return self::GATEWAYS;
        }

        return array_merge(self::GATEWAYS, self::GIFTCARDS);
    }

    /**
     * Return an array with all MultiSafepay gateways ids
     *
     * @return array
     */
    public function get_gateways_ids(): array {
        $gateways_ids = array();
        foreach ($this->get_payment_methods() as $gateway_id => $gateway) {
            $gateways_ids[] = $gateway_id;
        }
        return $gateways_ids;
    }

}
