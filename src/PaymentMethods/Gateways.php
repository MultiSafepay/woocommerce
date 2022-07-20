<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Babycadeaubon;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Beautywellness;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Boekenbon;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Fashioncheque;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Fashiongiftcard;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Fietsenbon;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Gezondheidsbon;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Givacard;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Good4fun;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Goodcard;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Nationaletuinbon;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Parfumcadeaukaart;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Podium;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Sportenfit;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Vvvcadeaukaart;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Webshopgiftcard;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Wellnessgiftcard;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Wijncadeau;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Winkelcheque;
use MultiSafepay\WooCommerce\PaymentMethods\Giftcards\Yourgift;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Afterpay;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Alipay;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\AlipayPlus;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Amex;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\ApplePay;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Bancontact;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\BankTrans;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Belfius;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Cbc;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\CreditCard;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Dbrtp;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Dirdeb;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Dotpay;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Einvoicing;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Eps;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Generic;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Generic2;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Generic3;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Giropay;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Ideal;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\IdealQr;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\In3;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\IngHomePay;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Kbc;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Klarna;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Maestro;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\MasterCard;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\MultiSafepay;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\MyBank;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\PayAfterDelivery;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\PayPal;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Paysafecard;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Santander;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Sofort;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Trustly;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Visa;

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
        'multisafepay_alipay_plus'       => AlipayPlus::class,
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
        'multisafepay_einvoice'          => Einvoicing::class,
        'multisafepay_eps'               => Eps::class,
        'multisafepay_generic'           => Generic::class,
        'multisafepay_generic_2'         => Generic2::class,
        'multisafepay_generic_3'         => Generic3::class,
        'multisafepay_giropay'           => Giropay::class,
        'multisafepay_ideal'             => Ideal::class,
        'multisafepay_idealqr'           => IdealQr::class,
        'multisafepay_in3'               => In3::class,
        'multisafepay_kbc'               => Kbc::class,
        'multisafepay_klarna'            => Klarna::class,
        'multisafepay_maestro'           => Maestro::class,
        'multisafepay_mastercard'        => MasterCard::class,
        'multisafepay_mybank'            => MyBank::class,
        'multisafepay_payafter'          => PayAfterDelivery::class,
        'multisafepay_paypal'            => PayPal::class,
        'multisafepay_paysafecard'       => Paysafecard::class,
        'multisafepay_santander'         => Santander::class,
        'multisafepay_sofort'            => Sofort::class,
        'multisafepay_trustly'           => Trustly::class,
        'multisafepay_visa'              => Visa::class,

        'multisafepay_babycadeaubon'     => Babycadeaubon::class,
        'multisafepay_beautyandwellness' => Beautywellness::class,
        'multisafepay_boekenbon'         => Boekenbon::class,
        'multisafepay_fashioncheque'     => Fashioncheque::class,
        'multisafepay_fashiongiftcard'   => Fashiongiftcard::class,
        'multisafepay_fietsenbon'        => Fietsenbon::class,
        'multisafepay_gezondheidsbon'    => Gezondheidsbon::class,
        'multisafepay_givacard'          => Givacard::class,
        'multisafepay_good4fun'          => Good4fun::class,
        'multisafepay_goodcard'          => Goodcard::class,
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
     * Return the WooCommerce payment method object by MultiSafepay gateway code.
     *
     * @param string $code
     * @return mixed
     */
    public static function get_payment_method_object_by_gateway_code( string $code ) {
        foreach ( self::GATEWAYS as $gateway ) {
            $gateway = new $gateway();
            if ( $gateway->get_payment_method_code() === $code ) {
                return $gateway;
            }
        }
        return false;
    }

    /**
     * Return the WooCommerce payment method object by WooCommerce payment method id.
     *
     * @param string $payment_method_id
     * @return mixed
     */
    public static function get_payment_method_object_by_payment_method_id( string $payment_method_id ) {
        if ( ! isset( self::GATEWAYS[ $payment_method_id ] ) ) {
            return false;
        }
        $gateway = self::GATEWAYS[ $payment_method_id ];
        return new $gateway();
    }


    /**
     * Get all active MultiSafepay payment options
     *
     * @return array
     */
    public static function get_gateways_with_payment_component(): array {
        $gateways_with_payment_component = array();
        foreach ( self::GATEWAYS as $gateway ) {
            $gateway = new $gateway();
            if ( $gateway->is_payment_component_enable() ) {
                $gateways_with_payment_component[] = $gateway->id;
            }
        }
        return $gateways_with_payment_component;
    }

}
