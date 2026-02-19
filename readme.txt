=== MultiSafepay plugin for WooCommerce ===
Contributors: multisafepayplugin
Tags: multisafepay, payment gateway, credit cards, ideal, bnpl
Requires at least: 6.0
Tested up to: 6.9.1
Requires PHP: 7.3
Stable tag: 6.11.1
License: GPL-3.0-or-later

MultiSafepay offers the most comprehensive payment solutions. Easily integrate the payment solutions of MultiSafepay into your webshop.

== Description ==

**About MultiSafepay**
MultiSafepay is a collecting payment service provider which means we take care of the agreements, technical details and
payment collection required for each payment method. You can start selling online today and manage all your transactions
from one place.

**Supported Payment Methods**

Payment methods:
By default, any payment method you activate in your MultiSafepay account will be available to be activated in the plugin, but you can also choose to show only specific payment methods in your checkout.
The plugin supports all the payment methods available in your MultiSafepay account, including but not limited to:

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
* Download the plugin from the WordPress Plugin Directory
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
The customer will receive the payment link in the email send by WooCommerce with the order details. Also, the payment link will be added to the order notes.

Please follow these steps:

1. Login into your backend and navigate to WooCommerce -> Orders -> Add order.
2. Register the order details as explained in [WooCommerce documentation](https://docs.woocommerce.com/document/managing-orders/#section-16).
3. In "Order actions" panel; select the option "Email invoice / order details to customer".
4. Click on "Create" order button.
5. An email will be sent to the customer with the details of the order and a payment link to finish the order.
6. The payment link will be available for the customer in their private account area, in "Orders" section.

= Can I refund orders? =

Yes, you can fully or partially refund transactions directly from your WooCommerce backend for all payment methods.
You can also refund from your [MultiSafepay Control](https://merchant.multisafepay.com)

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

= Release Notes - MultiSafepay 6.11.1 (Feb 19th, 2026) =

### Fixed
+ PLGWOOS-1035: Fix incorrect plugin version constant in settings and order request

= Release Notes - MultiSafepay 6.11.0 (Feb 19th, 2026) =

### Added
+ PLGWOOS-1018: Add payment icons to WooCommerce Checkout Blocks

### Fixed
+ PLGWOOS-1032: Fix WC_Blocks_Utils::has_block_in_page() fails to detect checkout block when nested inside other blocks
+ PLGWOOS-1019: Fix the duplicated 'multisafepay_time_unit' setting field argument

### Changed
+ PLGWOOS-1029: REST Response Cache Control

= Release Notes - MultiSafepay 6.10.0 (Nov 10th, 2025) =

### Added
+ PLGWOOS-1010: Add contract type selection for Billink between B2C and B2B
+ PLGWOOS-1006: Add support custom image size for Bancontact QR codes

### Fixed
+ PLGWOOS-956: MultiSafepay PostePay is being listed as only one payment method, but it should be two

= Release Notes - MultiSafepay 6.9.0 (Jun 5th, 2025) =

### Added
+ PLGWOOS-1003: Add zip code and email format validation to QR code implementation
+ PLGWOOS-1001: Improvement over QR code implementation validating checkout when "ship to a different address" is checked.

### Fixed
+ PLGWOOS-1009: Fix Bancontact QR showing up even when it hasn’t been explicitly enabled
+ PLGWOOS-1007: Refund request based on amount is including the checkout data, even when it's not needed.
+ PLGWOOS-997: Validate zip code checkout fields to prevent payments via Wallets
+ PLGWOOS-1002: Payment Component shows "Store my details for future visits" field, when user is not logged in
+ PLGWOOS-999: Fix URL Parameter Concatenation in QR Payment Redirect Flow
+ PLGWOOS-1000: Remove unneeded get_customer_ip_address() and get_user_agent() methods in QrCustomerService

### Changed
+ PLGWOOS-1005: Adjusting the minimum discrepancy allowed to filter Billink tax rates
+ PLGWOOS-1004: Adding shipping method name in the Order Request, instead of generic label "Shipping"

= Release Notes - MultiSafepay 6.8.3 (Apr 9th, 2025) =

### Fixed
+ PLGWOOS-992: Prevent processing a request on the REST QR related endpoints using a security token

= Release Notes - MultiSafepay 6.8.2 (Apr 7th, 2025) =

### Fixed
+ PLGWOOS-990: Prevent processing a request on the REST QR related endpoints if this one is disabled
+ PLGWOOS-989: Fix system report payment type to show when QR or QR only is enabled
+ PLGWOOS-987: Typecast as string the value that will be defined as merchant Item ID within the QR request

= Release Notes - MultiSafepay 6.8.1 (Apr 4th, 2025) =

### Fixed
+ PLGWOOS-984: Catch and handle InvalidArgumentException when validating the IP Address within the order request
+ PLGWOOS-982: Return early when QR webhook request fails

= Release Notes - MultiSafepay 6.8.0 (Apr 1st, 2025) =

### Added
+ PLGWOOS-978: Add Payment Component QR

### Fixed
+ PLGWOOS-957: Fix filter that returns payment methods that supports payment component to return only enabled methods

= Release Notes - MultiSafepay 6.7.3 (Mar 4th, 2025) =

### Fixed
+ PLGWOOS-973: Round shopping cart item tax rates in BILLINK gateway

= Release Notes - MultiSafepay 6.7.2 (Feb 14th, 2025) =

### Fixed
+ PLGWOOS-971: Fix iDEAL payment method not being shown on WooCommerce Checkout Blocks

= Release Notes - MultiSafepay 6.7.1 (Feb 7th, 2025) =

### Added
+ PLGWOOS-968: Add system report values from the payment method's user role setting and enhance logging for filter methods

= Release Notes - MultiSafepay 6.7.0 (Jan 28th, 2025) =

### Added
+ PLGWOOS-967: Add filter per user role
+ PLGWOOS-960: Add a transaction link in the order detail view in admin

### Changed
+ DAVAMS-868: Block refunds for Multibanco

### Fixed
+ DAVAMS-875: Setup gift cards max amount to 0, because it allows partial payment
+ PLGWOOS-963: Fix redirection after canceling a payment, when the user is using the "order-pay" endpoint

= Release Notes - MultiSafepay 6.6.2 (Nov 5th, 2024) =

### Added
+ PLGWOOS-961: Add double-check before addDelivery() in the order request builder

### Changed
+ PLGWOOS-953: Change label of the group cards setting field

= Release Notes - MultiSafepay 6.6.1 (Sep 4th, 2024) =

### Fixed
+ PLGWOOS-952: Fix System Report failing because WC_API not found

### Removed
+ PLGWOOS-950: Remove iDEAL issuers of the payment component

= Release Notes - MultiSafepay 6.6.0 (Jul 8th, 2024) =

### Added
+ PLGWOOS-946: Add support for branded credit and debit cards

### Changed
+ PLGWOOS-943: Refactor PaymentMethodCallback class (#574)
+ PLGWOOS-948: General improvements to increase unit test coverage

= Release Notes - MultiSafepay 6.5.1 (Jun 7th, 2024) =

### Fixed
+ PLGWOOS-936: Fix the values set as min and max amount from payment method API request
+ PLGWOOS-937: Fix Payment Components, where the amount is wrongly being set

= Release Notes - MultiSafepay 6.5.0 (May 22nd, 2024) =

### Added
+ PLGWOOS-925: Add support for missing payments methods in WooCommerce Checkout Blocks

### Fixed
+ PLGWOOS-933: Fix conflict with Query Monitor
+ PLGWOOS-934: Fix the Google Pay button duplication issue
+ PLGWOOS-935: Fix Payment Component on order-pay page endpoint, not loading if the shopping cart is empty

= Release Notes - MultiSafepay 6.4.3 (May 17th, 2024) =

### Fixed
+ PLGWOOS-922: Reduce the amount of API request improving general performance of the plugin

= Release Notes - MultiSafepay 6.4.2 (May 6th, 2024) =

### Fixed
+ PLGWOOS-922: Reduce the amount of request done to the payment-methods endpoint, from the admin

= Release Notes - MultiSafepay 6.4.1 (Apr 17th, 2024) =

### Fixed
+ PLGWOOS-920: Fix Apple Pay and Google Pay being shown in WooCommerce blocks, when are using direct payment buttons

= Release Notes - MultiSafepay 6.4.0 (Apr 17th, 2024) =

### Added
+ PLGWOOS-915: Add 'direct' transaction type for 'Bank Transfer' payment method

### Fixed
+ PLGWOOS-918: Fix overwriting the payment methods name in WooCommerce Blocks
+ DAVAMS-747: Fix 'template_id' within the Payment Components

= Release Notes - MultiSafepay 6.3.1 (Mar 13th, 2024) =

### Fixed
+ PLGWOOS-911: Fix initialisation or refreshing of the Payment Component when payment methods are assigned to specific country

### Changed
+ PLGWOOS-912: Ignore offline action - notifications related with pretransactions

= Release Notes - MultiSafepay 6.3.0 (Mar 11th, 2024) =

### Added
+ PLGWOOS-866: Add Apple Pay and Google Pay direct

= Release Notes - MultiSafepay 6.2.1 (Feb 1st, 2024) =

### Fixed
+ PLGWOOS-902: Support for WooCommerce Checkout Blocks for redirect payment methods
+ PLGWOOS-901: Remove duplicated method reinit_payment_component()

= Release Notes - MultiSafepay 6.2.0 (Nov 13th, 2023) =

### Added
+ PLGWOOS-872: Add support for [High-Performance Order Storage](https://woo.com/document/high-performance-order-storage/)

= Release Notes - MultiSafepay 6.1.2 (Oct 19th, 2023) =

### Fixed
+ PLGWOOS-886: Fix the assignation of the payment method, when the selected payment method changes on the payment page, and is selected a credit card or debit card payment method, and "Group Credit Cards" setting field is enabled.

### Changed
+ PLGWOOS-890: Bring back the payment component setting field to allow users to disable it.

= Release Notes - MultiSafepay 6.1.1 (Oct 16th, 2023) =

### Fixed
+ PLGWOOS-887: Disable Payment Components for Gift Cards payment methods, even when API is returning Payment Component is allowed

= Release Notes - MultiSafepay 6.1.0 (Oct 11th, 2023) =

### Added
+ PLGWOOS-884: Add in the system report missing settings for each payment method

### Changed
+ PLGWOOS-882: Enable Payment Components by default in all payment methods where is available


= Release Notes - MultiSafepay 6.0.0 (Oct 4th, 2023) =

### Added
+ PLGWOOS-803: Add support to register the payment methods dynamically, via API request.
+ PLGWOOS-859: Trigger checkout validations of the fields related to the payment component on checkout submission.
+ PLGWOOS-857: Add support for partial refunds for [BNPL payment methods](https://docs.multisafepay.com/docs/bnpl)
