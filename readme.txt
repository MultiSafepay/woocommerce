=== MultiSafepay plugin for WooCommerce ===
Contributors: multisafepayplugin
Tags: multisafepay, credit card, credit cards, gateway, payments, woocommerce, ideal, bancontact, klarna, sofort, giropay, sepa direct debit
Requires at least: 5.0
Tested up to: 5.6.1
Requires PHP: 7.2
Stable tag: 4.1.0
License: MIT

MultiSafepay offers the most comprehensive payment solutions. Easily integrate the payment solutions of MultiSafepay into your webshop.

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
* Betaal per Maand
* Dotpay
* E-Invoicing
* EPS
* Giropay
* iDEAL
* in3
* ING Home'Pay
* KBC/CBC
* Klarna
* Maestro
* Mastercard
* Pay After Delivery
* PayPal
* Paysafecard
* Request to Pay
* SEPA Direct Debit
* SOFORT Banking
* Trustly
* Visa

Giftcards:
* Baby Cadeaubon
* Beauty & Wellness
* Boekenbon
* Fashioncheque
* Fashion Giftcard‎
* Gezondheidsbon
* GivaCard
* Good4fun Giftcard
* Goodcard
* Fietsbon
* Nationale Tuinbon
* Parfum Cadeaukaart
* Podium
* Sport & Fit
* VVV Giftcard
* Webshop gift card
* Wellness gift card
* Wijncadeau
* Winkelcheque
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
* Navigate to _WooCommerce_ -> _MultiSafepay Settings_
* In _Account_ tab, set the API key. Information about the API key can be found on our [API key page](https://docs.multisafepay.com/tools/multisafepay-control/get-your-api-key/). Click on _Save changes_ button.
* Go to _Order Status_ tab and confirm the match between WooCommerce order statuses and MultiSafepay order statuses. Click on _Save changes_ button.
* Go to _Options_ tab and confirm the settings for each field. Click on _Save changes_ button.
* Navigate to _WooCommerce_ -> _Settings_ -> _Payments_. Click on the payment methods you would like to offer, check and set or confirm the settings for those been enable. Click on _Save changes_ button.


== Frequently Asked Questions ==

= How can I install the plugin for WooCommerce? =

* Installation instruction can be found in our [Manual](https://docs.multisafepay.com/integrations/plugins/woocommerce/manual/) page.

= How can I update the plugin for WooCommerce? =

Before you update the plugin, we strongly recommend you the following:

* Make sure you have a backup of your production environment
* Test the plugin in a staging environment.
* Go to our [Manual](https://docs.multisafepay.com/integrations/plugins/woocommerce/manual) page, download the plugin and follow the instructions from step 2.

= How can I generate a payment link in the backend of WooCommerce? =

It is possible to generate a payment link when an order has been created at the backend in WooCommerce.
The customer will receive the payment link in the email send by WooCommerce with the order details. Also the payment link will be added to the order notes.

Please follow these steps:

1. Login into your backend and navigate to WooCommerce -> Orders -> Add order.
2. Register the order details as explained in [WooCommerce documentation](https://docs.woocommerce.com/document/managing-orders/#section-16).
3. In "Order actions" panel; select the option "Email invoice / order details to customer".
4. Click on "Create" order button.
5. An email will be sended to the customer with the details of the order and a payment link to finish the order.
6. The payment link will be available for the customer in their private account area, in "Orders" section.

= Can I refund orders? =

Yes, you can fully or partially refund transactions directly from your WooCommerce backend for all payment methods, except for [Billing Suite](https://docs.multisafepay.com/payment-methods/billing-suite) payment methods in which it is only possible to process full refunds.
You can also refund from your [MultiSafepay Control](https://merchant.multisafepay.com)


== Upgrade Notice ==

= 4.1.0 =
4.1.0 version is a major upgrade, a complete rewrite of the plugin. After upgrade, please navigate to MultiSafepay settings page and confirm the settings.

== Screenshots ==

1. MultiSafepay Settings Page - Account section
2. MultiSafepay Settings Page - Order status section
3. MultiSafepay Settings Page - Options section
4. MultiSafepay Settings Page - Support section
5. List of MultiSafepay payment methods in WooCommerce settings
6. Checkout page with MultiSafepay payment methods

== Changelog ==

= Release Notes - WooCommerce 4.1.0 (Feb 17th, 2021) =

### Added
PLGWOOS-512: Add support for tokenization.
PLGWOOS-521: Change order status on callback even if merchant did not save the settings, using defaults.
PLGWOOS-530: Process notification, even when the payment method returned by MultiSafepay is not registered as WooCommerce gateway.
PLGWOOS-531: Avoid process refund if amount submited in backend is 0

### Fixed
PLGWOOS-535: Fix bug min_amount filter
PLGWOOS-536: Fix instructions in multi select country field
PLGWOOS-518: Fix protocol of notification URL
PLGWOOS-526: Fix typo error in AfterPay payment method title
PLGWOOS-523: Fix type of transaction to redirect for Dotpay payment method

### Changed
PLGWOOS-519: Improvement for coupons support in ShoppingCart.
PLGWOOS-528: Refactor gender and salutation fields to process different validation messages
PLGWOOS-503: Move debug mode field to options section

### Removed
PLGWOOS-525: Remove validation in backend for MultiSafepay payment method
PLGWOOS-516: Avoid initialize the plugin if WooCommerce is not active


= Release Notes - WooCommerce 4.0.0 (Unreleased) =

### Added
+ Complete rewrite of the plugin
+ Full and partial refunds for non billing suite payment methods
+ Full refunds for billing suite payment methods
+ Set MultiSafepay transactions as shipped when order reach the defined status in settings
+ Set MultiSafepay transaction as invoiced when order reach the defined status in settings
+ Filter payment methods by country
+ Filter payment methods by maximum amount of order
+ Filter payment methods by minimum amount of order
+ Custom initialized status for each payment method
+ Validations in backend settings fields
+ Support for compound taxes

### Changed
+ PLGWOOS-410: Refactor plugin using the [PHP-SDK](https://github.com/MultiSafepay/php-sdk)

### Removed
+ Remove FastCheckout
