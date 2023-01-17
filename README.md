# Worldline Online Payments

## Credit card

[![M2 Coding Standard](https://github.com/wl-online-payments-direct/plugin-magento-creditcard/actions/workflows/coding-standard.yml/badge.svg?branch=develop)](https://github.com/wl-online-payments-direct/plugin-magento-creditcard/actions/workflows/coding-standard.yml)
[![M2 Mess Detector](https://github.com/wl-online-payments-direct/plugin-magento-creditcard/actions/workflows/mess-detector.yml/badge.svg?branch=develop)](https://github.com/wl-online-payments-direct/plugin-magento-creditcard/actions/workflows/mess-detector.yml)

This is a module for the credit card (iFrame) Worldline payment solution.

To install this solution, you may use
[adobe commerce marketplace](https://marketplace.magento.com/worldline-module-magento-payment.html)
or install it from the github.

This solution is also included into [main plugin for adobe commerce](https://github.com/wl-online-payments-direct/plugin-magento).
### Change log:

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
- Option added to enforce Strong Customer Authentication for every 3DS request
- Improvements and support for 2.3.x magento versions
- General code improvements and bug fixes

#### 1.3.1
- Improve work for multi website instances

#### 1.3.0
- Improve the "waiting" page
- Add the "pending" page

#### 1.2.0
- Bug fixes and general code improvements

#### 1.1.1
- Hide the checkbox "save card" for iFrame checkout (Credit Card payment method) for guests and when the vault is disabled
- PWA improvements and support
- Bug fixes and general code improvements

#### 1.1.0
- Waiting page has been added after payment is done to correctly process webhooks and create the order
- Asyncronic order creation through get calls when webhooks suffer delay
- Refund flow is improved for multi-website instances
- General improvements and bug fixes

#### 1.0.0
- Initial MVP version 
