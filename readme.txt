=== MultiSafepay plugin for WooCommerce ===
Contributors: multisafepayplugin
Tags: multisafepay, payment gateway, credit cards, ideal, bnpl
Requires at least: 6.0
Tested up to: 6.7.1
Requires PHP: 7.3
Stable tag: 6.7.1
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
* Dotpay
* E-Invoicing
* EPS
* Giropay
* iDEAL
* iDEAL+in3
* in3
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
* Fashion Giftcard
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
* In _Account_ tab, set the API key. Information about the API key can be found on our [API key page](https://docs.multisafepay.com/docs/sites#site-id-api-key-and-security-code). Click on _Save changes_ button.
* Go to _Order Status_ tab and confirm the match between WooCommerce order statuses and MultiSafepay order statuses. Click on _Save changes_ button.
* Go to _Options_ tab and confirm the settings for each field. Click on _Save changes_ button.
* Navigate to _WooCommerce_ -> _Settings_ -> _Payments_. Click on the payment methods you would like to offer, check and set or confirm the settings for those been enabled. Click on _Save changes_ button.


== Frequently Asked Questions ==

= How can I install the plugin for WooCommerce? =

* Installation instruction can be found in our [Manual](https://docs.multisafepay.com/docs/woocommerce) page.

= How can I update the plugin for WooCommerce? =

Before you update the plugin, we strongly recommend you the following:

* Make sure you have a backup of your production environment
* Test the plugin in a staging environment.
* Go to our [Manual](https://docs.multisafepay.com/docs/woocommerce) page, download the plugin and follow the instructions from step 2.

= How can I generate a payment link in the backend of WooCommerce? =

It is possible to generate a payment link when an order has been created at the backend in WooCommerce.
The customer will receive the payment link in the email send by WooCommerce with the order details. Also the payment link will be added to the order notes.

Please follow these steps:

1. Login into your backend and navigate to WooCommerce -> Orders -> Add order.
2. Register the order details as explained in [WooCommerce documentation](https://docs.woocommerce.com/document/managing-orders/#section-16).
3. In "Order actions" panel; select the option "Email invoice / order details to customer".
4. Click on "Create" order button.
5. An email will be sent to the customer with the details of the order and a payment link to finish the order.
6. The payment link will be available for the customer in their private account area, in "Orders" section.

= Can I refund orders? =

Yes, you can fully or partially refund transactions directly from your WooCommerce backend for all payment methods, except for [Buy now pay later](https://docs.multisafepay.com/docs/bnpl) payment methods in which it is only possible to process full refunds.
You can also refund from your [MultiSafepay Control](https://merchant.multisafepay.com)

== Upgrade Notice ==

= 6.7.1 =
6.x.x is a major upgrade in which the MultiSafepay payment methods are registered dynamically via an API request to MultiSafepay. If you are upgrading from 5.X.X version, after the upgrade, please navigate to the MultiSafepay settings page, and to each one of the payment methods enabled in your account, and confirm the settings in each section are set up according to your preferences.

== Screenshots ==

1. MultiSafepay Settings Page - Account section
2. MultiSafepay Settings Page - Order status section
3. MultiSafepay Settings Page - Options section
4. MultiSafepay Settings Page - Logs section
5. MultiSafepay Settings Page - System report section
6. MultiSafepay Settings Page - Support section
7. List of MultiSafepay payment methods in WooCommerce settings
8. Checkout page with MultiSafepay payment methods
9. Embedded Credit Card form using MultiSafepay Payment Component

== Changelog ==
= Release Notes - WooCommerce 6.7.1 (Feb 7th, 2025) =

### Added
+ PLGWOOS-968: Add system report values from the payment method's user role setting and enhance logging for filter methods

= Release Notes - WooCommerce 6.7.0 (Jan 28th, 2025) =

### Added
+ PLGWOOS-967: Add filter per user role
+ PLGWOOS-960: Add a transaction link in the order detail view in admin

### Changed
+ DAVAMS-868: Block refunds for Multibanco

### Fixed
+ DAVAMS-875: Setup gift cards max amount to 0, because it allows partial payment
+ PLGWOOS-963: Fix redirection after canceling a payment, when the user is using the "order-pay" endpoint

= Release Notes - WooCommerce 6.6.2 (Nov 5th, 2024) =

### Added
+ PLGWOOS-961: Add double-check before addDelivery() in the order request builder

### Changed
+ PLGWOOS-953: Change label of the group cards setting field

= Release Notes - WooCommerce 6.6.1 (Sep 4th, 2024) =

### Fixed
+ PLGWOOS-952: Fix System Report failing because WC_API not found

### Removed
+ PLGWOOS-950: Remove iDEAL issuers of the payment component

= Release Notes - WooCommerce 6.6.0 (Jul 8th, 2024) =

### Added
+ PLGWOOS-946: Add support for branded credit and debit cards

### Changed
+ PLGWOOS-943: Refactor PaymentMethodCallback class (#574)
+ PLGWOOS-948: General improvements to increase unit test coverage

= Release Notes - WooCommerce 6.5.1 (Jun 7th, 2024) =

### Fixed
+ PLGWOOS-936: Fix the values set as min and max amount from payment method API request
+ PLGWOOS-937: Fix Payment Components, where the amount is wrongly being set

= Release Notes - WooCommerce 6.5.0 (May 22nd, 2024) =

### Added
+ PLGWOOS-925: Add support for missing payments methods in WooCommerce Checkout Blocks

### Fixed
+ PLGWOOS-933: Fix conflict with Query Monitor
+ PLGWOOS-934: Fix the Google Pay button duplication issue
+ PLGWOOS-935: Fix Payment Component on order-pay page endpoint, not loading if the shopping cart is empty

= Release Notes - WooCommerce 6.4.3 (May 17th, 2024) =

### Fixed
+ PLGWOOS-922: Reduce the amount of API request improving general performance of the plugin

= Release Notes - WooCommerce 6.4.2 (May 6th, 2024) =

### Fixed
+ PLGWOOS-922: Reduce the amount of request done to the payment-methods endpoint, from the admin

= Release Notes - WooCommerce 6.4.1 (Apr 17th, 2024) =

### Fixed
+ PLGWOOS-920: Fix Apple Pay and Google Pay being shown in WooCommerce blocks, when are using direct payment buttons

= Release Notes - WooCommerce 6.4.0 (Apr 17th, 2024) =

### Added
+ PLGWOOS-915: Add 'direct' transaction type for 'Bank Transfer' payment method

### Fixed
+ PLGWOOS-918: Fix overwriting the payment methods name in WooCommerce Blocks
+ DAVAMS-747: Fix 'template_id' within the Payment Components

= Release Notes - WooCommerce 6.3.1 (Mar 13th, 2024) =

### Fixed
+ PLGWOOS-911: Fix initialisation or refreshing of the Payment Component when payment methods are assigned to specific country

### Changed
+ PLGWOOS-912: Ignore offline action - notifications related with pretransactions

= Release Notes - WooCommerce 6.3.0 (Mar 11th, 2024) =

### Added
+ PLGWOOS-866: Add Apple Pay and Google Pay direct

= Release Notes - WooCommerce 6.2.1 (Feb 1st, 2024) =

### Fixed
+ PLGWOOS-902: Support for WooCommerce Checkout Blocks for redirect payment methods
+ PLGWOOS-901: Remove duplicated method reinit_payment_component()

= Release Notes - WooCommerce 6.2.0 (Nov 13th, 2023) =

### Added
+ PLGWOOS-872: Add support for [High-Performance Order Storage](https://woo.com/document/high-performance-order-storage/)

= Release Notes - WooCommerce 6.1.2 (Oct 19th, 2023) =

### Fixed
+ PLGWOOS-886: Fix the assignation of the payment method, when the selected payment method changes on the payment page, and is selected a credit card or debit card payment method, and "Group Credit Cards" setting field is enabled.

### Changed
+ PLGWOOS-890: Bring back the payment component setting field to allow users to disable it.

= Release Notes - WooCommerce 6.1.1 (Oct 16th, 2023) =

### Fixed
+ PLGWOOS-887: Disable Payment Components for Gift Cards payment methods, even when API is returning Payment Component is allowed

= Release Notes - WooCommerce 6.1.0 (Oct 11th, 2023) =

### Added
+ PLGWOOS-884: Add in the system report missing settings for each payment method

### Changed
+ PLGWOOS-882: Enable Payment Components by default in all payment methods where is available

= Release Notes - WooCommerce 5.4.1 (Sep 27th, 2023) =

### Fixed
+ PLGWOOS-878: Fix Payment Components not being shown.

= Release Notes - WooCommerce 5.4.0 (Sep 26th, 2023) =

### Added
+ PLGWOOS-870: Add support to define completed as a final order status where notification will not change the order status

### Fixed
+ PLGWOOS-871: Fix the minimum amount filter failing in the order-pay page

### Changed
+ DAVAMS-665 General refactor for better error handling

= Release Notes - WooCommerce 5.3.0 (Aug 10th, 2023) =

### Added
+ DAVAMS-636: Add Zinia payment method

### Changed
+ DAVAMS-640: Refactor of the Payment Component

= Release Notes - WooCommerce 5.2.2 (Jun 19th, 2023) =

### Fixed
+ PLGWOOS-852: Fix typo in description of Pay After Delivery Installments

### Changed
+ DAVAMS-621: Rename "Credit Card" payment method as "Card payment"
+ PLGWOOS-844: Change API Keys settings field to password type

= Release Notes - WooCommerce 5.2.1 (Feb 22nd, 2023) =

### Fixed
+ PLGWOOS-850: Fix 'Tested up to' value in readme.txt file to reflect the latest WordPress version tested, instead of the latest WooCommerce set by mistake

= Release Notes - WooCommerce 5.2.0 (Feb 22nd, 2023) =

### Added
+ DAVAMS-599: Add new setting field to disable the shopping cart within the order request
+ DAVAMS-573: Add Pay After Delivery Installments payment method

### Removed
+ DAVAMS-571: Remove Google Analytics tracking ID within the OrderRequest object
+ PLGWOOS-815: Remove uninstall script

### Changed
+ DAVAMS-581: Rebrand Pay After Delivery with new logos

= Release Notes - WooCommerce 5.1.2 (Jan 10th, 2022) =

### Fixed
+ PLGWOOS-842: Fix Riverty terms and conditions field validation when payment method is set as redirect type

### Changed
+ PLGWOOS-840: Update Dutch and Belgian translations

= Release Notes - WooCommerce 5.1.1 (Dec 6th, 2022) =

### Changed
+ DAVAMS-547: AfterPay -> Riverty rebrand
+ PLGWOOS-837: Declare support for Wordpress version 6.1 and WooCommerce version 7.1

= Release Notes - WooCommerce 5.1.0 (Sep 30th, 2022) =

### Added
+ PLGWOOS-828: Add Google Pay
+ PLGWOOS-516: Add Amazon Pay
+ PLGWOOS-516: Add support for [WooCommerce Blocks](https://wordpress.org/plugins/woo-gutenberg-products-block/) for redirect payment methods

### Fixed
+ PLGWOOS-553: Fix deprecated docs links

= Release Notes - WooCommerce 5.0.0 (Sep 1st, 2022) =

### Added
+ PLGWOOS-829: Improvement over the error handling in MultiSafepayClient

### Changed
+ PLGWOOS-827: Drop support for PHP 7.2

= Release Notes - WooCommerce 4.17.2 (Jul 22st, 2022) =

### Fixed
+ PLGWOOS-825: Fix an issue in which some payment methods are not being shown in the checkout, because of the setting field country selector is assuming the wrong value in some cases

= Release Notes - WooCommerce 4.17.1 (Jul 22st, 2022) =

### Changed
+ PLGWOOS-817: Improvement in the escaping of the outputs of the settings page

= Release Notes - WooCommerce 4.17.0 (Jul 21st, 2022) =

### Removed
+ PLGWOOS-816: Remove validation to check if a gateway is enabled in the merchant account, before activate the WooCommerce payment method
+ PLGWOOS-818: Remove upgrade notice functionality in plugin list page

### Changed
+ PLGWOOS-817: Improvement in sanitization and validation of the inputs, and escaping the outputs

= Release Notes - WooCommerce 4.16.0 (Jul 20th, 2022) =

### Added
+ DAVAMS-490: Add MyBank payment method

### Removed
+ PLGWOOS-811: Remove download plugin logs button and related methods

= Release Notes - WooCommerce 4.15.0 (May 25th, 2022) =

### Added
+ DAVAMS-470: Add terms and conditions checkbox to AfterPay

### Changed
+ PLGWOOS-805: Declare support for Wordpress 6.0

= Release Notes - WooCommerce 4.14.0 (May 19th, 2022) =

### Added
+ DAVAMS-476: Add Alipay+

### Changed
+ PLGWOOS-804: Use default locale if get_locale returns null to prevent third party plugin errors
+ PHPSDK-93: Upgrade the [PHP-SDK](https://github.com/MultiSafepay/php-sdk) dependency to 5.5.0

= Release Notes - WooCommerce 4.13.1 (Mar 23th, 2022) =

### Added
+ PLGWOOS-792: Declare support for Wordpress 5.9.2 and WooCommerce 6.3.1
+ PLGWOOS-790: Improvement on debug mode, logging the body of the POST notification request

### Fixed
+ PLGWOOS-791: Fix error when WooCommerce order is not found after receive a valid POST notification

= Release Notes - WooCommerce 4.13.0 (Feb 1st, 2022) =

### Added
+ PLGWOOS-770: Add payment component support for payment options: Visa, Mastercard, Maestro and American Express
+ PLGWOOS-774: Add support to process 'smart_coupon' coupons from Smart Coupons third party plugin
+ PLGWOOS-775: Log shopping cart content when debug mode is enabled

= Release Notes - WooCommerce 4.12.0 (Jan 13th, 2022) =

### Added
+ PLGWOOS-769: Add new filter 'multisafepay_merchant_item_id' to allow third party developers overwrite the merchant_item_id property within the ShoppingCart object

### Changed
+ PLGWOOS-744: Update 'Betaal per Maand' default max_amount value, according with new product rules
+ PLGWOOS-759: Rebrand Sofort payment method

= Release Notes - WooCommerce 4.11.0 (Jan 4th, 2022) =

### Added
+ PLGWOOS-745: Add Payment Component

### Changed
+ PLGWOOS-765: Refactor PaymentMethodsController::generate_orders_from_backend() to work only with one argument and avoiding conflicts with third party plugins
+ PLGWOOS-745: Tokenization now works through the Payment Component

### Fixed
+ PLGWOOS-763: Fix error on plugin list when application can not connect with wordpress network

= Release Notes - WooCommerce 4.10.0 (Dec 13th, 2021) =

### Added
+ PLGWOOS-748: Add PHP-SDK version to system report
+ PLGWOOS-758: Add filter to turn notifications to GET method

### Removed
+ DAVAMS-460: Remove ING Home’Pay

### Changed
+ PLGWOOS-695: Replace HTTP Client, use WP_HTTP instead kriswallsmith/buzz
+ PLGWOOS-749: Replace logo of Bancontact for new one

### Fixed
+ PLGWOOS-752: Fix missing placeholder for Test API Key input field in settings page

= Release Notes - WooCommerce 4.9.0 (Oct 18th, 2021) =

### Added
+ PLGWOOS-715: Add 2 "Generic Gateways" which include a flexible gateway code that allows any merchant to connect to almost every payment method we offer.
+ PLGWOOS-746: Declare support for Wordpress 5.8.1 and WooCommerce 5.8.0

### Changed
+ PLGWOOS-740: Improve the helper text of the Google Analytics ID setting field, adding a link to Documentation Center
+ PLGWOOS-747: Upgrade the [PHP-SDK](https://github.com/MultiSafepay/php-sdk) component to 5.3.0

### Fixed
+ PLGWOOS-739: Fix fatal error related with undefined method when processing orders using iDEAL QR
+ PLGWOOS-743: Fix broken links to Documentation Center in settings page

= Release Notes - WooCommerce 4.8.3 (Sep 6th, 2021) =

### Fixed
+ PLGWOOS-737: Fix error related with refunds by updating the PHP-SDK to 5.2.1

= Release Notes - WooCommerce 4.8.2 (Sep 2nd, 2021) =

### Added
+ PLGWOOS-730: Declare support for WooCommerce 5.6.0

= Release Notes - WooCommerce 4.8.1 (Aug 9th, 2021) =

### Fixed
+ PLGWOOS-727: Show error message from the API in the checkout page, when there is an error on direct transactions

= Release Notes - WooCommerce 4.8.0 (Aug 4th, 2021) =

### Added
+ PLGWOOS-723: Declare support for WooCommerce 5.5.2 and Wordpress 5.8
+ PLGWOOS-711: Add missing titles in setting pages

### Changed
+ PLGWOOS-718: Remove PSP ID string when register the transaction ID in WC_Order->payment_complete()

= Release Notes - WooCommerce 4.7.0 (Jun 23th, 2021) =

### Added
+ PLGWOOS-706: Declare support for WooCommerce 5.4.1

### Changed
+ PLGWOOS-672: Change notification method from GET to [POST](https://docs.multisafepay.com/faq/api/notification-url/#get-vs-post-notification) by default

### Fixed
+ PLGWOOS-704: Log errors in the MultiSafepay log file, when processing notifications.

= Release Notes - WooCommerce 4.6.0 (May 19th, 2021) =

### Added
+ PLGWOOS-625: Add log section in MultiSafepay settings page
+ PLGWOOS-666: Add MultiSafepay system status section in settings page
+ PLGWOOS-376: Add support to show upgrade notices in plugin list
+ PLGWOOS-657: Add nl_BE language

### Fixed
+ PLGWOOS-694: Fix notification for orders fully paid with gift cards
+ PLGWOOS-692: Fix Second Chance within the orderRequest object
+ PLGWOOS-654: Fix the gateway_id assigned to the properties of each token

= Release Notes - WooCommerce 4.5.1 (Apr 7th, 2021) =

### Fixed
+ PLGWOOS-661: Fix payment methods ids to match list of gateway lists keys, which was producing an error to process notification for Sofort payments
+ PLGWOOS-663: Fix stock decreasing error, in relation with Bank Transfer gateway and notification flows

= Release Notes - WooCommerce 4.5.0 (Mar 31th, 2021) =

### Fixed
+ PLGWOOS-659: Fix initialization of the plugin on multisite environments in which WooCommerce has been activate network wide

### Added
+ PLGWOOS-534: Add generic gateway

= Release Notes - WooCommerce 4.4.1 (Mar 25th, 2021) =

### Fixed
+ PLGWOOS-653: Fix overwriting initial order status when transaction is initialized

= Release Notes - WooCommerce 4.4.0 (Mar 23th, 2021) =

### Fixed
+ PLGWOOS-648: Return 0 as tax rate, if WooCommerce taxes are disabled but tax rules are registered
+ PLGWOOS-647: Add verification to check if the token used in the transaction belongs to the customer

### Added
+ PLGWOOS-651: Add setting to select type of transaction in SEPA Direct Debit, E-Invoicing, in3, Santander Consumer Finance, AfterPay and iDEAL
+ PLGWOOS-644: Add setting to select type of transaction in Pay After Delivery
+ PLGWOOS-640: Add setting to select type of transaction in Bank Transfer

= Release Notes - WooCommerce 4.3.0 (Mar 18th, 2021) =

### Fixed
+ PLGWOOS-626: Fix order not being cancelled when customer cancels the order
+ PLGWOOS-630: Fix include shipping item in full refund of billing suite payment methods

### Added
+ PLGWOOS-629: Add shipping item to the order request, even if this one is free
+ PLGWOOS-631: Add delivery address in order request even if the shipping amount is 0
+ PLGWOOS-634: Add settings field to redirect to checkout page or cart page on cancelling the order
+ PLGWOOS-635: Add suggestion to set default initial order status for bank transfer to wc-on-hold
+ PLGWOOS-636: Add notification endpoint from version 3.8.0 to process deprecated notifications

### Changed
+ PLGWOOS-622: Change notification url for all payment methods to a single notification url

= Release Notes - WooCommerce 4.2.2 (Mar 16th, 2021) =

### Fixed
+ PLGWOOS-632: Fix undefined method get_the_user_ip
+ PLGWOOS-621: Fix division by zero when fee price is 0

= Release Notes - WooCommerce 4.2.1 (Mar 11th, 2021) =

### Fixed
+ PLGWOOS-613: Fix error related with multiple forwarded IPs by updating the PHP-SDK to 5.0.1

### Added
+ PLGWOOS-398: Add support to change the data in the OrderRequest using Wordpress filters

### Changed
+ PLGWOOS-614: Avoid changing order status if transaction is partially refunded

= Release Notes - WooCommerce 4.2.0 (Mar 9th, 2021) =

### Changed
+ PLGWOOS-602: Move invoice and shipped settings field from order status tab to options tab
+ PLGWOOS-602: Remove completed status from order status tab in settings page
+ PLGWOOS-601: Change default status for declined transactions from wc-cancelled to wc-failed

### Fixed
+ PLGWOOS-599: Fix typo in string message when payment method changes
+ PLGWOOS-598: Replace hardcoded url using plugins_url function
+ PLGWOOS-605: Fix description of country filter field

### Added
+ PLGWOOS-603: Add setting field for custom order description
+ PLGWOOS-604: Add forwarded IP to the CustomerDetails object
+ PLGWOOS-597: Support for orders with is_vat_exempt
+ PLGWOOS-606: Add chargedback transaction status in plugin settings

= Release Notes - WooCommerce 4.1.8 (Mar 5th, 2021) =

### Changed
+ PLGWOOS-593: Register PSP ID in WooCommerce order using order complete payment method
+ PLGWOOS-593: Change notification method on completed status to use $order->complete_payment()

### Fixed
+ PLGWOOS-594: Fix Credit Card payment method form, to show description if customer is not logged in

= Release Notes - WooCommerce 4.1.7 (Mar 3th, 2021) =

### Changed
+ PLGWOOS-579: Remove warning message on validation, when enabling CREDITCARD gateway

### Fixed
+ PLGWOOS-584: Fix conflict with third party plugins related with Discovery exception
+ PLGWOOS-585: Set MultiSafepay transaction as shipped or invoiced using order number instead of order id

= Release Notes - WooCommerce 4.1.6 (Mar 2nd, 2021) =

### Added
+ PLGWOOS-574: Add locale support

### Changed
+ PLGWOOS-575: Change settings page capability requirement from manage_options to manage_woocommerce

### Fixed
+ PLGWOOS-580: Show credit card payment method description in checkout
+ PLGWOOS-569: Remove class that trigger validation styles for ideal select in checkout page


= Release Notes - WooCommerce 4.1.5 (Feb 24th, 2021) =

### Fixed
+ PLGWOOS-552: Fix product item price with discounts introduced by third party plugins (#252)


= Release Notes - WooCommerce 4.1.4 (Feb 23th, 2021) =

### Fixed
+ PLGWOOS-563: Remove some nonce validations to support custom checkouts forms (#249)
+ PLGWOOS-550: Typecast cart item quantity to int to avoid errors in the PHP-SDK (#248)

### Changed
+ PLGWOOS-556: Change composer dependencies to avoid conflicts with other plugins (#247)
+ PLGWOOS-562: Add fallback for in3, in case no fields is filled in checkout, convert the transaction to redirect type (#250)


= Release Notes - WooCommerce 4.1.3 (Feb 21th, 2021) =

### Fixed
+ PLGWOOS-549: Support custom order numbers generated by third party plugins in notification method
+ PLGWOOS-551: Resize logo if theme used by merchant do not support WooCommerce


= Release Notes - WooCommerce 4.1.2 (Feb 19th, 2021) =

### Fixed
+ PLGWOOS-548: Fix iDEAL gateway if no issuer selected in checkout


= Release Notes - WooCommerce 4.1.1 (Feb 18th, 2021) =

### Changed
+ PLGWOOS-545: Remove API Key validation


= Release Notes - WooCommerce 4.1.0 (Feb 17th, 2021) =

### Added
+ PLGWOOS-512: Add support for tokenization.
+ PLGWOOS-521: Change order status on callback even if merchant did not save the settings, using defaults.
+ PLGWOOS-530: Process notification, even when the payment method returned by MultiSafepay is not registered as WooCommerce gateway.
+ PLGWOOS-531: Avoid process refund if amount submited in backend is 0

### Fixed
+ PLGWOOS-535: Fix bug min_amount filter
+ PLGWOOS-536: Fix instructions in multi select country field
+ PLGWOOS-518: Fix protocol of notification URL
+ PLGWOOS-526: Fix typo error in AfterPay payment method title
+ PLGWOOS-523: Fix type of transaction to redirect for Dotpay payment method

### Changed
+ PLGWOOS-519: Improvement for coupons support in ShoppingCart.
+ PLGWOOS-528: Refactor gender and salutation fields to process different validation messages
+ PLGWOOS-503: Move debug mode field to options section

### Removed
+ PLGWOOS-525: Remove validation in backend for MultiSafepay payment method
+ PLGWOOS-516: Avoid initialize the plugin if WooCommerce is not active


= Release Notes - WooCommerce 4.0.0 (internal release) (Feb 12th, 2021) =

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
