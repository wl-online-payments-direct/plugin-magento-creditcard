# Worldline Online Payments

## Credit card

[![M2 Coding Standard](https://github.com/wl-online-payments-direct/plugin-magento-creditcard/actions/workflows/coding-standard.yml/badge.svg?branch=develop)](https://github.com/wl-online-payments-direct/plugin-magento-creditcard/actions/workflows/coding-standard.yml)
[![M2 Mess Detector](https://github.com/wl-online-payments-direct/plugin-magento-creditcard/actions/workflows/mess-detector.yml/badge.svg?branch=develop)](https://github.com/wl-online-payments-direct/plugin-magento-creditcard/actions/workflows/mess-detector.yml)

This is a module for the credit card (iFrame) Worldline payment solution.

To install this solution, you may use
[adobe commerce marketplace](https://marketplace.magento.com/worldline-module-magento-payment.html)
or install it from the GitHub.

This solution is also included into [main plugin for adobe commerce](https://github.com/wl-online-payments-direct/plugin-magento).
### Change log:

#### 1.19.0
- Fixed issue where FPT (Fixed Product Tax) rates were not taken into account.
- Update "wl-online-payments-direct/sdk-php" library to 5.16.1

#### 1.18.0
- Improved display of shipping costs on the payment page for Hosted Checkout and Redirect Payment.

#### 1.17.0
- Added trusted URLs to the CSP whitelist.
- Improved reliability of fallback cron job.
- Fixed credentials caching issue when simultaneously processing refunds for multiple merchant IDs.

#### 1.16.0
- Improved the order creation process by tracking multiple paymentIDs.
- Improved logging and exception handling when multiple payments are done for a single order.

#### 1.15.0
- Added new payment method "Bank Transfer by Worldline".
- Added the "Contact email" field to the feature suggestion form.
- Added compatibility with Php Sdk 5.10.0.
- Replaced legacy Alipay payment method with the new Alipay+.
- Replaced legacy WeChat Pay payment method with the new version.
- Fixed validation error when placing orders with Virtual/downloadable products.
- Fixed error when adding new shipping address on checkout.

#### 1.14.0
- Added email to customer and “Copy To” for "Auto Refund For Out Of Stock Orders" notifications.
- Added translations for French (Belgium), French (Switzerland) and Dutch (Belgium).
- Improved notifications so they are only sent once per event.
- Improved "Failed Orders Notifications" to avoid triggering on transaction status 46.
- Fixed "Redirect Payments" display issue after customer modifies shipping options.
- Fixed server error on checkout page when "Specific Currencies" are not aligned with Magento’s non-default currencies.

#### 1.13.0
- Added "Session Timeout" configuration for the hosted checkout page.
- Added "Allowed Number Of Payment Attempts" configuration for the hosted checkout page.
- Added compatibility with Php Sdk 5.8.2.
- Added refund refused notifications functionality.
- Fixed update of the credit memo status when the refund request was refused by acquirer.

#### 1.12.1
- Fixed issue with partial invoices and partial credit memos.
- Fixed transaction ID value for request to check if payment can be cancelled.

#### 1.12.0
- Added own branded gift card compatibility for Intersolve payment method.
- Added compatibility with Php Sdk 5.7.0.
- Modified plugin tab "dynamic order status synchronization" to “Settings & Notifications”.
- Fixed value determination process for "AddressIndicator" parameter.
- Fixed issues with creating orders by cron.
- Fixed issue with Magento confirmation page when using PayPal payment method.
- Fixed issue with auto refund for out-of-stock feature.
- Fixed issue when using a database prefix.

#### 1.11.0
- Added new payment method “Union Pay International".
- Added new payment method “Przelewy24".
- Added new payment method “EPS".
- Added new payment method “Twint".
- Added compatibility with Php Sdk 5.6.0.
- Added compatibility with Amasty Subscriptions & Recurring Payments extension 1.6.15.
- Improved plugin landing page "About Worldline".
- Improved Hosted Tokenization error message when transaction is declined.
- Improved concatenation of streetline1 and streetline2 for billing & shipping address.

#### 1.10.0
- Added new payment method “Giftcard Limonetik".
- Added new setting "Enable Sending Payment Refused Emails".
- Improved handling of Magento 2 display errors.
- Fixed hosted tokenization js link for production transactions.
- Fixed order creation issue on successful transactions.
- Fixed webhooks issue for rejected transactions with empty refund object.
- General code improvements.

#### 1.9.3
- Fixed issue of products with special pricing not displaying the original price in order view.
- Fixed issue with configurable product on cart restoration when user clicks the browser back button.
- Fixed issue with last payment id not fetched properly.
- Fixed issue where carts are restored incompletely.
- Fixed issue when customer attribute doesn't display in order after paying.
- Added customer address attributes validation before placing order.
- Added a setting to stop sending refusal emails.
- Added compatibility with Php Sdk 5.4.0.

#### 1.9.2
- Add support for the 5.3.0 version of PHP SDK.
- Fix connection credential caching.

#### 1.9.1
- Add support for the 5.1.0 version of PHP SDK.
- Add integration tests.
- General code improvements.

#### 1.9.0
- Add support for Magento 2.4.6.
- Add support for the 5.0.0 version of PHP SDK.
- Add integration tests.
- General code improvements.

#### 1.8.2
- Add fix for Adobe Commerce cloud instances.

#### 1.8.1
- Improve performance on the checkout page.
- Add backend address validation before payments.
- General code improvements and bug fixes.

#### 1.8.0
- Add surcharge functionality (for the Australian market).
- Add Sepa Direct Debit payment method.
- Add the ability to save the Sepa Direct Debit mandate and use it through the Magento vault.
- Extract GraphQl into a dedicated extension.
- Add Integration tests.
- General code improvements and bug fixes.

#### 1.7.1
- Support the 13.0.0 version of PWA.

#### 1.7.0
- Add price restrictions for currencies having specific decimals rules (like JPY).
- Move 3-D Secure settings to the general tab under the "Payment Methods" menu.
- Change names and tooltips of the 3-D Secure settings.
- Add integration tests.
- Add infrastructure for integration tests.
- General code improvements and bug fixes.

#### 1.6.1
- Rise core version.

#### 1.6.0
- Improve cancel and void actions logic.
- Make the template setting not mandatory.
- Add uninstall script.
- Update release notes.
- General code improvements and bug fixes.

#### 1.5.0
- Add a feature to request 3DS exemption for transactions below 30 EUR.
- Add integration tests.
- General code improvements and bug fixes.

#### 1.4.0
- Option added to enforce Strong Customer Authentication for every 3DS request.
- Improvements and support for 2.3.x magento versions.
- General code improvements and bug fixes.

#### 1.3.1
- Improve work for multi website instances.

#### 1.3.0
- Improve the "waiting" page.
- Add the "pending" page.

#### 1.2.0
- Bug fixes and general code improvements.

#### 1.1.1
- Hide the checkbox "save card" for iFrame checkout (Credit Card payment method) for guests and when the vault is disabled.
- PWA improvements and support.
- Bug fixes and general code improvements.

#### 1.1.0
- Waiting page has been added after payment is done to correctly process webhooks and create the order.
- Asyncronic order creation through get calls when webhooks suffer delay.
- Refund flow is improved for multi-website instances.
- General improvements and bug fixes.

#### 1.0.0
- Initial MVP version.
