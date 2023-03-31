define([
    'jquery', 'ko', 'mage/url'
], function ($, ko, urlBuilder) {
        "use strict";

        return {
            staticSurchargeEnabled: window.checkoutConfig?.worldlineCreditCardCheckoutConfig?.isSurchargeEnabled,
            isSurchargeActive: ko.observable(
                window.checkoutConfig?.worldlineCreditCardCheckoutConfig?.isSurchargeEnabled
            ),
            isOscValid: ko.observable(true),
            paymentMethod: null,
            tokenizer: {},

            setCode: function (paymentMethod) {
                this.paymentMethod = paymentMethod;
            },

            getCode: function () {
                return this.paymentMethod;
            },

            initializeTokenizer: function (component, elementId, options, token) {
                if (!this.getCode()) {
                    return;
                }

                let url = this.getUrl();

                if (!url) {
                    return;
                }

                component.tokenizer = new Tokenizer(url, elementId, options, token);
                component.tokenizer.initialize()
                    .then(() => {
                        // after initialization methods
                    })
                    .catch(reason => {
                        // error handler
                    });
            },

            getUrl: function () {
                if (typeof window.checkoutConfig.payment[this.getCode()].url === 'undefined') {
                    $.ajax({
                        method: 'GET',
                        url: urlBuilder.build('wl_creditcard/tokenizer/url'),
                        contentType: "application/json",
                        async: false
                    }).done($.proxy(function (data) {
                        window.checkoutConfig.payment[this.getCode()].url = data.url;
                    }, this));
                }

                return window.checkoutConfig.payment[this.getCode()].url;
            }
        };
    }
);
