define([
    'mage/storage',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer'
], function (storage, quote, urlBuilder, customer) {
    'use strict';

    return function (hostedTokenizationId) {
        let serviceUrl, payload, headers = {};

        payload = {
            cartId: quote.getQuoteId(),
            hostedTokenizationId: hostedTokenizationId,
        };

        if (customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/carts/mine/worldline/credit-card-surcharge', {});
        } else {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/worldline/credit-card-surcharge', {
                cartId: quote.getQuoteId()
            });
            payload.email = quote.guestEmail;
        }

        return storage.post(
            serviceUrl, JSON.stringify(payload), true, 'application/json', headers
        )
    };
});
