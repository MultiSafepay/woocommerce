# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

***

## 6.8.0
Release Date: Apr 1st, 2025

### Added
+ PLGWOOS-978: Add Payment Component QR

### Fixed
+ PLGWOOS-957: Fix filter that returns payment methods that supports payment component to return only enabled methods

***

## 6.7.3
Release Date: Mar 4th, 2025

### Fixed
+ PLGWOOS-973: Round shopping cart item tax rates in BILLINK gateway

***

## 6.7.2
Release Date: Feb 14th, 2025

### Fixed
+ PLGWOOS-971: Fix iDEAL payment method not being shown on WooCommerce Checkout Blocks

***

## 6.7.1
Release Date: Feb 7th, 2025

### Added
+ PLGWOOS-968: Add system report values from the payment method's user role setting and enhance logging for filter methods

***

## 6.7.0
Release Date: Jan 28th, 2025

### Added
+ PLGWOOS-967: Add filter per user role
+ PLGWOOS-960: Add a transaction link in the order detail view in admin

### Changed
+ DAVAMS-868: Block refunds for Multibanco

### Fixed
+ DAVAMS-875: Setup gift cards max amount to 0, because it allows partial payment
+ PLGWOOS-963: Fix redirection after canceling a payment, when the user is using the "order-pay" endpoint

***

## 6.6.2
Release Date: Nov 5th, 2024

### Added
+ PLGWOOS-961: Add double-check before addDelivery() in the order request builder

### Changed
+ PLGWOOS-953: Change label of the group cards setting field

***

## 6.6.1
Release Date: Sep 4th, 2024

### Fixed
+ PLGWOOS-952: Fix System Report failing because WC_API not found

### Removed
+ PLGWOOS-950: Remove iDEAL issuers of the payment component

***

## 6.6.0
Release Date: Jul 8th, 2024

### Added
+ PLGWOOS-946: Add support for branded credit and debit cards

### Changed
+ PLGWOOS-943: Refactor PaymentMethodCallback class
+ PLGWOOS-948: General improvements to increase unit test coverage 

***

## 6.5.1
Release Date: Jun 7th, 2024

### Fixed
+ PLGWOOS-936: Fix the values set as min and max amount from payment method API request
+ PLGWOOS-937: Fix Payment Components, where the amount is wrongly being set

***

## 6.5.0
Release Date: May 22nd, 2024

### Added
+ PLGWOOS-925: Add support for missing payments methods in WooCommerce Checkout Blocks

### Fixed
+ PLGWOOS-933: Fix conflict with Query Monitor
+ PLGWOOS-934: Fix the Google Pay button duplication issue
+ PLGWOOS-935: Fix Payment Component on order-pay page endpoint, not loading if the shopping cart is empty

***

## 6.4.3
Release Date: May 17th, 2024

### Fixed
+ PLGWOOS-922: Reduce the amount of API request improving general performance of the plugin

***

## 6.4.2
Release Date: May 6th, 2024

### Fixed
+ PLGWOOS-922: Reduce the amount of request done to the payment-methods endpoint, from the admin

***

## 6.4.1
Release Date: Apr 17th, 2024

### Fixed
+ PLGWOOS-920: Fix Apple Pay and Google Pay being shown in WooCommerce blocks, when are using direct payment buttons

***

## 6.4.0
Release Date: Apr 17th, 2024

### Added
+ PLGWOOS-915: Add 'direct' transaction type for 'Bank Transfer' payment method

### Fixed
+ PLGWOOS-918: Fix overwriting the payment methods name in WooCommerce Blocks
+ DAVAMS-747: Fix 'template_id' within the Payment Components

***

## 6.3.1
Release Date: Mar 13th, 2024

### Fixed
+ PLGWOOS-911: Fix initialisation or refreshing of the Payment Component when payment methods is assigned to specific country

### Changed
+ PLGWOOS-912: Ignore offline action - notifications related with pretransactions

***

## 6.3.0
Release Date: Mar 11th, 2024

### Added
+ PLGWOOS-866: Add Apple Pay and Google Pay direct

***

## 6.2.1
Release Date: Feb 1st, 2024

### Fixed
+ PLGWOOS-902: Support for WooCommerce Checkout Blocks for redirect payment methods
+ PLGWOOS-901: Remove duplicated method reinit_payment_component()

***

## 6.2.0
Release Date: Nov 13th, 2023

### Added
+ PLGWOOS-872: Add support for [High-Performance Order Storage](https://woo.com/document/high-performance-order-storage/)

***

## 6.1.2
Release Date: Oct 19th, 2023

### Fixed
+ PLGWOOS-886: Fix the assignation of the payment method, when the selected payment method changes on the payment page, and is selected a credit card or debit card payment method, and "Group Credit Cards" setting field is enabled.

### Changed
+ PLGWOOS-890: Bring back the payment component setting field to allow users to disable it.

***

## 6.1.1
Release Date: Oct 16th, 2023

### Fixed
+ PLGWOOS-887: Disable Payment Components for Gift Cards payment methods, even when API is returning Payment Component is allowed

***

## 6.1.0
Release Date: Oct 11th, 2023

### Added
+ PLGWOOS-884: Add in the system report missing settings for each payment method

### Changed
+ PLGWOOS-882: Enable Payment Components by default in all payment methods where is available

***

## 6.0.0
Release Date: Oct 4th, 2023

### Added
+ PLGWOOS-803: Add support to register the payment methods dynamically, via API request.
+ PLGWOOS-859: Trigger checkout validations of the fields related to the payment component on checkout submission.
+ PLGWOOS-857: Add support for partial refunds for [BNPL payment methods](https://docs.multisafepay.com/docs/bnpl)

***

## 5.4.1
Release Date: Sep 27th, 2023

### Fixed
+ PLGWOOS-878: Fix Payment Components not being shown.

## 5.4.0
Release Date: Sep 26th, 2023

### Added
+ PLGWOOS-870: Add support to define completed as a final order status where notification will not change the order status

### Fixed
+ PLGWOOS-871: Fix the minimum amount filter failing in the order-pay page

### Changed
+ DAVAMS-665 General refactor for better error handling

***

## 5.3.0
Release Date: Aug 10th, 2023

### Added
+ DAVAMS-636: Add Zinia payment method

### Changed
+ DAVAMS-640: Refactor of the Payment Component

***

## 5.2.2
Release Date: Jun 19th, 2023

### Fixed
+ PLGWOOS-852: Fix typo in description of Pay After Delivery Installments

### Changed
+ DAVAMS-621: Rename "Credit Card" payment method as "Card payment"
+ PLGWOOS-844: Change API Keys settings field to password type

***

## 5.2.1
Release Date: Feb 22nd, 2023

### Fixed
+ PLGWOOS-850: Fix 'Tested up to' value in readme.txt file to reflect the latest WordPress version tested, instead of the latest WooCommerce set by mistake

***

## 5.2.0
Release Date: Feb 22th, 2023

### Added
+ DAVAMS-599: Add new setting field to disable the shopping cart within the order request
+ DAVAMS-573: Add Pay After Delivery Installments payment method

### Removed
+ DAVAMS-571: Remove Google Analytics tracking ID within the OrderRequest object
+ PLGWOOS-815: Remove uninstall script

### Changed
+ DAVAMS-581: Rebrand Pay After Delivery with new logos

***

## 5.1.2
Release Date: Jan 10th, 2023

### Fixed
+ PLGWOOS-842: Fix Riverty terms and conditions field validation when payment method is set as redirect type

### Changed
+ PLGWOOS-840: Update Dutch and Belgian translations

***

## 5.1.1
Release Date: Dec 6th, 2022

### Changed
+ DAVAMS-547: AfterPay -> Riverty rebrand
+ PLGWOOS-837: Declare support for Wordpress version 6.1 and WooCommerce version 7.1

***

## 5.1.0
Release Date: Sep 30th, 2022

### Added
+ PLGWOOS-828: Add Google Pay
+ PLGWOOS-516: Add Amazon Pay
+ PLGWOOS-516: Add support for [WooCommerce Blocks](https://wordpress.org/plugins/woo-gutenberg-products-block/) for redirect payment methods

### Fixed
+ PLGWOOS-553: Fix deprecated docs links

***

## 5.0.0
Release Date: Sep 1st, 2022

### Added
+ PLGWOOS-829: Improvement over the error handling in MultiSafepayClient

### Changed
+ PLGWOOS-827: Drop support for PHP 7.2

***

## 4.17.2
Release date: Jul 22nd, 2022

### Fixed
+ PLGWOOS-825: Fix an issue in which some payment methods are not being shown in the checkout, because of the setting field country selector is assuming the wrong value in some cases

***

## 4.17.1
Release date: Jul 22nd, 2022

### Changed
+ PLGWOOS-817: Improvement in the escaping of the outputs of the settings page

***

## 4.17.0
Release date: Jul 21st, 2022

### Changed
+ PLGWOOS-817: Improvement in sanitization and validation of the inputs, and escaping the outputs

### Removed
+ PLGWOOS-816: Remove validation to check if a gateway is enabled in the merchant account, before activate the WooCommerce payment method
+ PLGWOOS-818: Remove upgrade notice functionality in plugin list page

***

## 4.16.0
Release date: Jul 20th, 2022

### Added
+ DAVAMS-490: Add MyBank payment method

### Removed
+ PLGWOOS-811: Remove download plugin logs button and related methods

***

## 4.15.0
Release date: May 25th, 2022

### Added
+ DAVAMS-470: Add terms and conditions checkbox to AfterPay

### Changed
+ PLGWOOS-805: Declare support for WordPress 6.0

***

## 4.14.0
Release date: May 19th, 2022

### Added
+ DAVAMS-476: Add Alipay+

### Changed
+ PLGWOOS-804: Use default locale if get_locale returns null to prevent third party plugin errors
+ PHPSDK-93: Upgrade the [PHP-SDK](https://github.com/MultiSafepay/php-sdk) dependency to 5.5.0

***

## 4.13.1
Release date: Mar 23th, 2022

### Added
+ PLGWOOS-792: Declare support for WordPress 5.9.2 and WooCommerce 6.3.1
+ PLGWOOS-790: Improvement on debug mode, logging the body of the POST notification request

### Fixed
+ PLGWOOS-791: Fix error when WooCommerce order is not found after receive a valid POST notification

***

## 4.13.0
Release date: Feb 1st, 2022

### Added
+ PLGWOOS-770: Add payment component support for payment methods: Visa, Mastercard, Maestro and American Express
+ PLGWOOS-774: Add support to process 'smart_coupon' coupons from [Smart Coupons](https://woocommerce.com/products/smart-coupons/) third party plugin
+ PLGWOOS-775: Log shopping cart content when debug mode is enabled

***

## 4.12.0
Release date: Jan 13th, 2022

### Added
+ PLGWOOS-769: Add new filter 'multisafepay_merchant_item_id' to allow third party developers overwrite the merchant_item_id property within the ShoppingCart object 

### Changed
+ PLGWOOS-744: Update 'Betaal per Maand' default max_amount value, according with new product rules
+ PLGWOOS-759: Rebrand Sofort payment method

***

## 4.11.0
Release date: Jan 4th, 2022

### Added
+ PLGWOOS-745: Add Payment Component

### Changed
+ PLGWOOS-765: Refactor PaymentMethodsController::generate_orders_from_backend() to work only with one argument and avoiding conflicts with third party plugins
+ PLGWOOS-745: Tokenization now works through the Payment Component

### Fixed
+ PLGWOOS-763: Fix error on plugin list when application can not connect with wordpress network

***

## 4.10.0
Release date: Dec 13th, 2021

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

***

## 4.9.0
Release date: Oct 18th, 2021

### Added
+ PLGWOOS-715: Add 2 "Generic Gateways" which include a flexible gateway code that allow any merchant to connect to almost every payment method we offer.
+ PLGWOOS-746: Declare support for WordPress 5.8.1 and WooCommerce 5.8.0 

### Changed
+ PLGWOOS-740: Improve the helper text of the Google Analytics ID setting field, adding a link to Documentation Center
+ PLGWOOS-747: Upgrade the [PHP-SDK](https://github.com/MultiSafepay/php-sdk) component to 5.3.0

### Fixed
+ PLGWOOS-739: Fix fatal error related with undefined method when processing orders using iDEAL QR
+ PLGWOOS-743: Fix broken links to Documentation Center in settings page

***

## 4.8.3
Release date: Sep 6th, 2021

### Fixed
+ PLGWOOS-737: Fix error related with refunds by updating the PHP-SDK to 5.2.1

***

## 4.8.2
Release date: Sep 2nd, 2021

### Added
+ PLGWOOS-730: Declare support for WooCommerce 5.6.0

***

## 4.8.1
Release date: Aug 9th, 2021

### Fixed
+ PLGWOOS-727: Show error message from the API in the checkout page, when there is an error on direct transactions

***

## 4.8.0
Release date: Aug 4th, 2021

### Added
+ PLGWOOS-723: Declare support for WooCommerce 5.5.2 and WordPress 5.8
+ PLGWOOS-711: Add missing titles in setting pages

### Changed
+ PLGWOOS-718: Remove PSP ID string when register the transaction ID in WC_Order->payment_complete()

***

## 4.7.0
Release date: Jun 23th, 2021

### Added
+ PLGWOOS-706: Declare support for WooCommerce 5.4.1

### Changed
+ PLGWOOS-672: Change notification method from GET to [POST](https://docs.multisafepay.com/faq/api/notification-url/#get-vs-post-notification) by default

### Fixed
+ PLGWOOS-704: Log errors in the MultiSafepay log file, when processing notifications.

***

## 4.6.0
Release date: May 19th, 2021

### Added
+ PLGWOOS-625: Add log section in MultiSafepay settings page
+ PLGWOOS-666: Add MultiSafepay system status section in settings page
+ PLGWOOS-376: Add support to show upgrade notices in plugin list
+ PLGWOOS-657: Add nl_BE language

### Fixed
+ PLGWOOS-694: Fix notifications for order fully paid with gift cards
+ PLGWOOS-692: Fix Second Chance within the orderRequest object
+ PLGWOOS-654: Fix the gateway_id assigned to the properties of each token

***

## 4.5.1
Release date: Apr 7th, 2021

### Fixed
+ PLGWOOS-661: Fix payment methods ids to match list of gateway lists keys, which was producing an error to process notification for Sofort payments
+ PLGWOOS-663: Fix stock decreasing error, in relation with Bank Transfer gateway and notification flows

***

## 4.5.0
Release date: Mar 31th, 2021

### Fixed
+ PLGWOOS-659: Fix initialization of the plugin on multisite environments in which WooCommerce has been activate network wide

### Added
+ PLGWOOS-534: Add generic gateway

***

## 4.4.1
Release date: Mar 25th, 2021

### Fixed
+ PLGWOOS-653: Fix overwriting initial order status when transaction is initialized

***

## 4.4.0
Release date: Mar 23th, 2021

### Fixed
+ PLGWOOS-648: Return 0 as tax rate, if WooCommerce taxes are disabled but tax rules are registered
+ PLGWOOS-647: Add verification to check if the token used in the transaction belongs to the customer

### Added
+ PLGWOOS-651: Add setting to select type of transaction in SEPA Direct Debit, E-Invoicing, in3, Santander Consumer Finance, AfterPay and iDEAL
+ PLGWOOS-644: Add setting to select type of transaction in Pay After Delivery
+ PLGWOOS-640: Add setting to select type of transaction in Bank Transfer

***

## 4.3.0
Release date: Mar 18th, 2021

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

***

## 4.2.2
Release date: Mar 16th, 2021

### Fixed
+ PLGWOOS-632: Fix undefined method get_the_user_ip
+ PLGWOOS-621: Fix division by zero when fee price is 0

***

## 4.2.1
Release date: Mar 11th, 2021

### Fixed
+ PLGWOOS-613: Fix error related with multiple forwarded IPs by updating the PHP-SDK to 5.0.1

### Added
+ PLGWOOS-398: Add support to change the data in the OrderRequest using WordPress filters

### Changed
+ PLGWOOS-614: Avoid changing order status if transaction is partially refunded

***

## 4.2.0
Release date: Mar 9th, 2021

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

***

## 4.1.8
Release date: Mar 5th, 2021

### Changed
+ PLGWOOS-593: Register PSP ID in WooCommerce order using order complete payment method 
+ PLGWOOS-593: Change notification method on completed status to use $order->complete_payment()

### Fixed
+ PLGWOOS-594: Fix Credit Card payment method form, to show description if customer is not logged in

***

## 4.1.7
Release date: Mar 3th, 2021

### Changed
+ PLGWOOS-579: Remove warning message on validation, when enabling CREDITCARD gateway

### Fixed
+ PLGWOOS-584: Fix conflict with third party plugins related with Discovery exception 
+ PLGWOOS-585: Set MultiSafepay transaction as shipped or invoiced using order number instead of order id

***

## 4.1.6
Release date: Mar 2nd, 2021

### Added
+ PLGWOOS-574: Add locale support

### Changed
+ PLGWOOS-575: Change settings page capability requirement from manage_options to manage_woocommerce

### Fixed
+ PLGWOOS-580: Show credit card payment method description in checkout
+ PLGWOOS-569: Remove class that trigger validation styles for ideal select in checkout page

***

## 4.1.5
Release date: Feb 24th, 2021

### Fixed
+ PLGWOOS-552: Fix product item price with discounts introduced by third party plugins (#252)

***

## 4.1.4
Release date: Feb 23th, 2021

### Fixed
+ PLGWOOS-563: Remove some nonce validations to support custom checkouts forms (#249)
+ PLGWOOS-550: Typecast cart item quantity to int to avoid errors in the PHP-SDK (#248)

### Changed
+ PLGWOOS-556: Change composer dependencies to avoid conflicts with other plugins (#247)  
+ PLGWOOS-562: Add fallback for in3, in case no fields is filled in checkout, convert the transaction to redirect type (#250)  

***

## 4.1.3
Release date: Feb 23th, 2021

### Fixed
+ PLGWOOS-549: Support custom order numbers generated by third party plugins in notification method
+ PLGWOOS-551: Resize logo if theme used by merchant do not support WooCommerce

***

## 4.1.2
Release date: Feb 19th, 2021

### Fixed
+ PLGWOOS-548: Fix iDEAL gateway if no issuer selected in checkout

***

## 4.1.1
Release date: Feb 18th, 2021

### Changed
+ PLGWOOS-545: Remove API Key validation

***

## 4.1.0
Release date: Feb 17th, 2021

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

***

## 4.0.0 [internal release]
Release date: Feb 12th, 2021

### Added
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
+ Complete rewrite of the plugin
+ PLGWOOS-410: Refactor plugin using the [PHP-SDK](https://github.com/MultiSafepay/php-sdk)

### Removed
+ Remove FastCheckout

***

## 3.8.0
Release date: Oct 29th, 2020

### Added
+ PLGWOOS-421: Add Good4fun Giftcard

### Changed
+ DAVAMS-313: Rebrand Klarna to Klarna - buy now, pay later
+ DAVAMS-296: Rebrand Direct Bank Transfer to Request to Pay
+ DAVAMS-282: Update name and logo for Santander

***

## 3.7.0
Release date: Aug 14th, 2020

### Added
+ DAVAMS-237: Add in3 payment method

***

## 3.6.1
Release date: Aug 5th, 2020

### Fixed
+ PLGWOOS-404: Fix setting order to shipped when DHL is used

***

## 3.6.0
Release date: Jul 22nd, 2020

### Added
+ DAVAMS-266: Add CBC payment method

### Fixed
+ PLGWOOS-403: Fix FastCheckout not working
+ PLGWOOS-400: Apply set to shipped status only for MultiSafepay orders

***

## 3.5.2
Release date: Jun 25th, 2020

### Fixed
+ PLGWOOS-401: Fix rounding issues in shopping cart
+ PLGWOOS-394: Fix issue with translations not correctly loaded
+ PLGWOOS-397: Fix database already exists error

***

## 3.5.1
Release date: Apr 24th, 2020

### Fixed
+ PLGWOOS-395: Javascript error when Apple Pay is disabled
+ PLGWOOS-389: Fix addressparser not parsing housenumber extension correctly

### Changed
+ PLGWOOS-240: Improve plugin loading by using PSR-4 autoloader

### Removed
+ PLGWOOS-391: Remove TrustPay
+ PLGWOOS-390: Remove FerBuy
+ PLGWOOS-380: Remove Erotiekbon
+ PLGWOOS-327: Remove Lief Cadeaukaart
+ PLGWOOS-326: Remove Fijncadeau

***

## 3.5.0
Release date: Mar 30th, 2020

### Added
+ PLGWOOS-363: Add Apple Pay
+ PLGWOOS-384: Add Direct Bank Transfer

***

## 3.4.0
Release date: Jan 6th, 2020

### Added
+ PLGWOOS-287: Add maximum amount restriction for credit cards
+ PLGWOOS-321: Add Ohmygood Cadeaukaart

### Changed
+ PLGWOOS-115: Make suitable for WordPress.org Plugin Directory
+ PLGWOOS-260: Change VVV Bon to VVV Cadeaukaart

### Fixed
+ PLGWOOS-319: Disable payment fields when payment description is empty

***

## 3.3.0
Release date: Dec 13th, 2019

### Added
+ PLGWOOS-291: Add IP validation when WooCommerce returns multiple IP addresses
+ PLGWOOS-203: Add compatibility with WPML

### Changed
+ PLGWOOS-245: Change Klarna from direct to redirect
+ PLGWOOS-275: Improve Dutch translation for 'Activate'
+ PLGWOOS-263: Correct ING Home'Pay spelling

### Removed
+ PLGWOOS-208: Remove the send invoice option from the backend

### Fixed
+ PLGWOOS-285: Fix the fatal error "Cannot redeclare error_curl_not_installed"
+ PLGWOOS-102: Prevent the Notification URL from executing when not initialized by MultiSafepay
+ PLGWOOS-266: Prevent errors from appearing in logs for notifications of pre-transactions
+ PLGWOOS-290: Resolve DivisionByZeroError bug occurring with fees 
+ Fix PHP notice incorrect use of reset in function parseIpAddress
+ Fix PHP notice undefined property when order set to shipped

***

## 3.2.0
Release date: Jul 6th, 2018

## Improvements ##

+ PLGWOOS-232: Add TrustPay payment method
+ PLGWOOS-213: Add support for external fee plugin(s)

## Fixes ##

+ PLGWOOS-176: Restrict autoload to load only MultiSafepay classes
+ PLGWOOS-191: Refactor the way an order and transaction are retrieved
+ PLGWOOS-241: Remove status request on setting to shipped
+ PLGWOOS-195: Update Klarna Invoice link
+ PLGWOOS-231: Update Klarna payment method logo
+ PLGWOOS-197: Correct MultiFactor Terms and Condition link
+ PLGWOOS-242: Remove terms and conditions for Einvoicing
+ PLGWOOS-244: Shipment name now used on payment page instead of type
+ PLGWOOS-243: Payment page shopping cart reorganized
+ PLGWOOS-253: FastCheckout load correct first and last name
+ PLGWOOS-235: Rename KBC/CBC to KBC
+ PLGWOOS-236: Rename ING-Homepay to ING HomePay
+ PLGWOOS-247: Notice message 'Undefined variable' for E-Invoice, Pay After Delivery and Klarna
+ PLGWOOS-249: Remove whitespace at file headers
+ PLGWOOS-259: Direct E-Invoice returns unnecessary message 'Missing gender'

***

## 3.1.0
Release date: Jun 15th, 2018

## Improvements ##

+ PLGWOOS-215 Add support for Santander Betaalplan
+ PLGWOOS-214 Add support for Afterpay
+ PLGWOOS-216 Add support for Trustly

## Fixes ##

+ PLGWOOS-221: Do not add Klarna invoice link when setting to Completed
+ PLGWOOS-218: Undefined property in error logs when cancelling order
+ PLGWOOS-226: getTimeActive didn't respect seconds

***

## 3.0.4
Release date: Feb 2nd, 2018

## Improvements ##

+ PLGWOOS-169 Support direct transactions for Alipay/ING/Belfius/KBC
+ PLGWOOS-174 Remove usage of deprecated functions
+ PLGWOOS-175 Remove unnecessary use of file_exists
+ PLGWOOS-178 Order status is only changed to 'expired' in case the current status is 'pending' or 'on-hold'.
+ PLGWOOS-179 Add text domain for ideal issuer error message
+ PLGWOOS-182 Add Alipay as payment method
+ PLGWOOS-186 Add dynamic retrieve of shipping methods during Fast Checkout
+ PLGWOOS-187 Do not allow refund when amount is zero or less
+ PLGWOOS-192 Check/add all translations

## Fixes ##
+ PLGWOOS-173 Fix deprecated notice getRealPaymentMethod
+ PLGWOOS-180 Incorrect order-id used to load the order for updating
+ PLGWOOS-181 function getGatewayCode not implemented for FastCheckout
+ PLGWOOS-183 Update version number of plug-in failed
+ PLGWOOS-184 Incorrect check if field is empty
+ PLGWOOS-193 Fix deprecated notice FastCheckout
+ PLGWOOS-194 Refund function checks wrong variable to determine if refund was succesfull
+ PLGWOOS-199 Correct wc_get_cart_url and wc_get_checkout_url
+ PLGWOOS-200 FastCheckout doesn't redirect to order-confirmation screen
+ PLGWOOS-202:Payment method updated for Second Chance on Processing

## Changes ##
+ PLGWOOS-189 Update version number to 3.0.4
+ PLGWOOS-198 Update ING gateway to INGHOME

***

## 3.0.3
Release date: Okt 10th, 2017

## Fixes ##

+ Menu's are able to edit again.
+ In some cases the customer was redirected to the cancel-url after a succesful iDEAL transaction.

***

## 3.0.2
Release date: Okt 10th, 2017

## Improvements ##

+ Add ING Home'Pay as payment method.
+ Add Belfius as payment method.
+ Add KBC/CBC as payment method.
+ Add configuration option for Google-Analytic code.
+ Add shopping-cart information to the transaction.
+ Update payment method in order, in case a customer pays the second change with an other payment method.
+ Update the dutch translations.

## Fixes ##
+ Fixed issue to prevent a warning message when the title of a gateway wasn't filled in the config.
+ Fixed issue with retrieve the correct external transaction ID.
+ Fixed issue on error 1027 (Invalid cart amount) caused by an invalid shipping-tax.
+ Fixed issue in function to set order-status to shipped for PAD, Klarna and E-Invoiced.
+ Fixed warning issue on function setToShipped.
+ Fixed issue on not accepting PAD orders caused by an divide by zero error.

## Changes ##
+ Remove (beta)functionality to determine if there is a new version available.
+ Restrict use of the plug-in to WooCommerce 2.2 and above.

***

## 3.0.0
Release date: April 5th, 2017

## Improvements ##

+ Compatible with PHP-7
+ Installation by standard WordPress method
+ Added Dutch language file
+ Added configuration option Karna Merchant-EID (for future use.)
+ Added Terms and Conditions for Klarna, Pay After Delivery and E-Invoicing.
+ Improve the way errors are logged.
+ Added PaySafeCard as payment method.
+ Added Nationale bioscoopbon as a giftcard.
+ Added option to the global MultiSafepay settings to enable/disable the giftcards as payment method.

## Fixes ##
+ Better algoritm to split address into street, housenumber
+ After complete FastCheckout transaction no order confirmation page was showed

## Changes ##
+ General plugin settings moved to the general checkout-options
+ Remove BabyGiftcard as payment method

***

## 2.2.7
Release date: November 2nd, 2016

## Improvements ##

+ Added EPS and FerBuy as payment methods
+ Added support for E-invoicing
+ Added an extra payment method gateway called "Creditcards"; grouping creditcard payment methods as a single dropdown option.

## Fixes ##
+ Resolved an issue resulting in not being able to pay using Direct iDEAL.
+ Resolved an issue where expiring payment sessions result in orders being marked as new after 30 days.

##Changes ##
+ Changed banktransfer to direct banktransfer

***

## 2.2.6
Release date: July 14th, 2016

## Improvements ##

+ Added support for WooCommerce version 2.6.2.

## Fixes ##
+ Resolved an issue resulting in not being able to pay using Direct iDEAL.

***

## 2.2.5
Release date: June 24th, 2016

## Improvements ##

+ Added support for partial refunds for orders paid using Klarna and Pay After Delivery.
+ Added support for Fast Checkout order refunds.
+ Improvements were made to the iDEAL banklist selector, and a notice will be shown if no bank was selected.

## Fixes ##
+ Updated the Bancontact logo

## Fixes ##
+ Resolved issues occuring with Pay After Delivery and Klarna when using discounts.
+ Made compatible with WooCommerce version 2.6.

***

## 2.2.4
Release date: March 8th, 2016

## Improvements ##

+ Pay After Delivery is now only visible for orders placed in The Netherlands.
+ Textual improvements for the option "Send the order confirmation".
+ Orders started with banktransfer are now set to On Hold, rather than "Pending Payment".
+ Uncleared orders are now set to On Hold, rather than "Pending Payment".
+ Improved the iDEAL description shown when no iDEAL issuer/bank has been selected.

## Fixes ##
+ Resolved a bug causing Error 1035 when refunding.
+ Changed the way coupons are applied, which previously resulted in a paid totals mismatch.

***

## 2.2.3
Release date: Feb 18, 2016

## Improvements ##

+ Added dotpay as a payment method
+ Klarna and Pay After Delivery transactions are now set to Shipped, if enabled and the order is set to Completed.
+ Pay After Delivery is now only available as a payment method if the selected country is "The Netherlands".
+ Multistores in WooCommerce are now supported.
+ Added Bunq as a supported iDEAL issuer

## Fixes ##
+ Refunds from within WooCommerce now also work when using the WooCommerce Sequential Order Numbers plugin.
+ Issues with Gateway restrictions based on minimum and maximum amount are resolved for Klarna and Pay After Delivery.
+ Fixed a bug causing the postalcode not to be added to the order when using Fast Checkout.
+ Removed WooCommerce mailer functions in the plug-in, which was added to avoid mailing issues.

***

## 2.2.2
Release date: Dec 14, 2015

## Improvements ##

+ Added Klarna reservationcode and link to the invoice in the order comments.
+ For KLARNA and PAD the orderstatus is set to shipped when order status is set to completed and this option is enabled in the configuration.
+ Added Goodcard as giftcard.

## Fixes ##
+ Fixed performance issue due to our plugin loaded the iDEAL issuers on every page..
+ Fixed housenumber is now correct parsed when using both address fields.
+ Fixed issue with wrong processing of some orderstatusses.
+ Fixed The FastCheckout button was not completly visable with latest updates of woocommerce default template.

***

## 2.2.1
Release date: Sep 30, 2015

## Improvements ##

+ Added Klarna as payment method.

## Fixes ##
+ Fixed issue that prevents MultiSafepay to add the orderstatus in the order comment.

***

## 2.2.0
Release date: May 21, 2015

## Improvements ##

+ Added an extra check to determine if the MultiSafepay class exists.
+ Debug option added to the plug-in for troubleshooting purposes.
+ Added improved payment method icons.
+ Added the MultiFactor agreement hyperlink.
+ Added Refund API support. Refunds via MultiSafepay can now be executed from the WooCommerce order/back-end.
+ Added a check to see if WooCommerce is active. The plug-in will not be loaded if not the case.

## Changes ##
+ Changed add_error(); to wc_add_notice();

## Fixes ##
+ Fixed some undefined notices and improved checks for page_id and the loading of the plugins.
+ Resolved the 'Cannot redeclare class' error.

***

## 2.1.0
Release date: Oct 15, 2014

## Improvements ##

+ Added Fast Checkout
+ Added coupon support for FCO
+ Added option to enable/disable fco button
+ Added DB Table to check if order is already created and if so go to normal updating process when using Fast Checkout
+ Added amount check that compares the calculated order total after creating the order and the transaction amount. If they are not equal then set to wc-on-hold status and add a note about the mismatch in amounts
+ Added Payafter as a separate plugin
+ Added amex as a separate plugin
+ Added paypal as a separate plugin
+ Added VISA as a separate plugin
+ Added mistercash as a separate plugin
+ Added Mastercard as a separate plugin
+ Added Maestro as a separate plugin
+ Added giropay as a separate plugin
+ Added sofort as a separate plugin
+ Added DirectDebit as a separate plugin
+ Added Banktransfer as a separate plugin
+ Added iDEAL as a separate plugin

## Changes ##
+ Changed the processing of the offline actions so that FCO transactions work
+ Process stock on process_payment
+ Use ordernumber instead of orderid so that the plugin is compatible with thirdparty sequential ordernumbers plugins
+ Removed gateway method from the main module. Gateways are now separate plugins
+ Removed images from main module. These are now loaded from each separate plugins
+ Removed version checks as this version is only for 2.2 and higher
+ Removed useless code from all plugins
+ Removed country and amount restrictions. WooCommerce changed things and broke the function

## Fixes ##
+ Fixed bug with status updates
+ Fixed new bug with coupons not beeing processed because of extra check on cart or order discount
+ Small fixes (o.a. reported by Mark Roeling)

***

## 1.0.6
Release date: Apr 15, 2014

## Improvements ##

+ Added support for direct Pay After Delivery

***

## 1.0.5
Release date: Mar 21, 2014

## Improvements ##

+ Added support for American Express
+ Added housenumber check

## Fixes ##
+ Fixed bug when customer canceled a payment
+ Fixed bug that causes a empty status
+ Fixed bug in refund check

***

## 1.0.4
Release date: Mar 06, 2014

## Improvements ##

+ Auto spit housenumber from address if needed

## Fixes ##
+ Fixed bug when customer canceled a payment
+ Fixed bug that causes a empty status
+ Fixed bug in refund check

***

## 1.0.3
Release date: Feb 19, 2014

## Improvements ##

+ Added support for WooCommerce 2.1.x
+ Added payment method Pay After Delivery
+ Changed payment name 'directebanking' to 'Sofort Banking'
+ Added support for thirdparty payment surcharge module
+ Added support for dollars and GBP
+ Added check for available issuers when paying by iDEAL
+ added orderid to the return url

## Fixes ##
+ Fixed bug that caused no order data to show on thankyou page

***

## 1.0.2
Release date: Aug 21, 2013

## Improvements ##

+ Optional send an invoice e-mail

## Fixes ##
+ Fixed bug in update order status
