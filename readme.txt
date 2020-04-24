=== MultiSafepay plugin for WooCommerce ===
Contributors: multisafepayplugin
Tags: multisafepay, woocommerce, plug-in, plugin, payment, gateway, ideal, visa
Requires at least: 4.9
Tested up to: 5.4
Requires PHP: 7.0
Stable tag: 3.5.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily integrate the payment solutions of MultiSafepay into your webshop.
This plugin supports all major payment methods.

== Description ==

**About MultiSafepay**
MultiSafepay is a collecting payment service provider which means we take care of the agreements, technical details and
payment collection required for each payment method. You can start selling online today and manage all your transactions
from one place.

**Supported Payment Methods**

Payment methods:

* AfterPay
* Alipay
* American Express
* Apple Pay
* Bank transfer
* Bancontact
* Belfius
* Betaalplan
* Direct Bank Transfer
* Direct Debit
* Dotpay
* E-Invoicing
* EPS
* Giropay
* iDEAL
* ING Home'Pay
* KBC
* Klarna
* Maestro
* Mastercard
* Pay After Delivery
* PayPal
* Paysafecard
* SOFORT Banking
* Trustly
* Visa (including CartaSi, Cartes Bancaires & Dankort).

Giftcards:

* Fashioncheque
* Fashion Giftcard‎
* Gezondheidsbon
* GivaCard
* Goodcard
* Nationale Bioscoopbon‎
* Nationale Fietsbon
* Nationale Tuinbon
* Ohmygood
* Parfum Cadeaukaart
* Podium Cadeaukaart
* SPORT&FIT Cadeau
* VVV Giftcard
* Webshop Giftcard
* Wellness Giftcard
* Wijn Cadeau
* Winkel Cheque
* YourGift


== Installation ==

To use the plugin you need a MultiSafepay account.
[Create an account](https://merchant.multisafepay.com/signup)

= Automatic Installation =
* Search for the 'MultiSafepay' plugin via 'Add New' from the WordPress menu
* Press the 'Install now' button

= Manual Installation =
* Download the plugin from the Wordpress Plugin Directory
* Unzip the downloaded file to a local directory
* Upload the directory 'multisafepay' to /wp-content/plugins/ on the remote server

= Configuration =
* Activate the 'MultiSafepay' plugin via 'Plugin' from the WordPress menu
* Configure the plugin via 'WooCommerce->Settings->Payments' from the WordPress menu


== Frequently Asked Questions ==

= Can I refund orders? =
Yes, you can directly from your backend.
You can also refund from your [MultiSafepay Control](https://merchant.multisafepay.com/signup)


== Upgrade Notice ==

= 3.4.0 =
If you are using 2.2.x of our plugin, please contact integration@multisafepay.com for assistance before updating.


== Changelog ==

= Release Notes - WooCommerce 3.5.1 (Apr 24th, 2020) =

### Changed ##
* PLGWOOS-240: Improve loading the plugin
* PLGWOOS-326: Remove giftcard Fijncadeau
* PLGWOOS-327: Remove giftcard Lief Cadeaukaart
* PLGWOOS-380: Remove giftcard Erotiekbon
* PLGWOOS-390: Remove payment method FerBuy
* PLGWOOS-391: Remove payment method TrustPay

### Fixed ##
* PLGWOOS-389: Fix parsing address into street and house number
* PLGWOOS-395: Fix Javascript error when Apple Pay is disabled

= Release Notes - WooCommerce 3.5.0 (Mar 30th, 2020) =

### Added ##
* PLGWOOS-363: Add Apple Pay
* PLGWOOS-384: Add Direct Bank Transfer

= Release Notes - WooCommerce 3.4.0 (Jan 6th, 2020) =

### Added ##
* PLGWOOS-287: Add maximum amount restriction for credit cards
* PLGWOOS-321: Add Ohmygood Cadeaukaart

### Changed ##
* PLGWOOS-115: Make suitable for WordPress.org Plugin Directory
* PLGWOOS-260: Change VVV Bon to VVV Cadeaukaart

### Fixed ##
* PLGWOOS-319: Disable payment fields when payment description is empty

= Release Notes - WooCommerce 3.3.0 (Dec 13th, 2019) =

### Added ##
* PLGWOOS-291: Add IP validation when WooCommerce returns multiple IP addresses
* PLGWOOS-203: Add compatibility with WPML

### Changed ##
* PLGWOOS-245: Change Klarna from direct to redirect
* PLGWOOS-275: Improve Dutch translation for 'Activate'
* PLGWOOS-263: Correct ING Home'Pay spelling

### Removed ##
* PLGWOOS-208: Remove the send invoice option from the backend

### Fixed ##
* PLGWOOS-285: Fix the fatal error "Cannot redeclare error_curl_not_installed"
* PLGWOOS-102: Prevent the Notification URL from executing when not initialized by MultiSafepay
* PLGWOOS-266: Prevent errors from appearing in logs for notifications of pre-transactions
* PLGWOOS-290: Resolve DivisionByZeroError bug occurring with fees
* Fix PHP notice incorrect use of reset in function parseIpAddress
* Fix PHP notice undefined property when order set to shipped

= Release Notes - Woo-Commerce 3.2.0 (Jul 6th, 2018) =

### Improvements ##
* PLGWOOS-232: Add TrustPay payment method
* PLGWOOS-213: Add support for external fee plugin(s)

### Fixes ##
* PLGWOOS-176: Restrict autoload to load only MultiSafepay classes
* PLGWOOS-191: Refactor the way an order and transaction are retrieved
* PLGWOOS-241: Remove status request on setting to shipped
* PLGWOOS-195: Update Klarna Invoice link
* PLGWOOS-231: Update Klarna payment method logo
* PLGWOOS-197: Correct MultiFactor Terms and Condition link
* PLGWOOS-242: Remove terms and conditions for Einvoicing
* PLGWOOS-244: Shipment name now used on payment page instead of type
* PLGWOOS-243: Payment page shopping cart reorganized
* PLGWOOS-253: FastCheckout load correct first and last name
* PLGWOOS-235: Rename KBC/CBC to KBC
* PLGWOOS-236: Rename ING-Homepay to ING HomePay
* PLGWOOS-247: Notice message 'Undefined variable' for E-Invoice, Pay After Delivery and Klarna
* PLGWOOS-249: Remove whitespace at file headers
* PLGWOOS-259: Direct E-Invoice returns unnecessary message 'Missing gender'


The complete changelog for all releases can be found here [Complete changelog](https://docs.multisafepay.com/integrations/woocommerce/changelog/)



