define([
    'jquery',
    'ko'
], function ($, ko) {
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

            initializeTokenizer: function (component) {
                if (!this.getCode()) {
                    return;
                }

                if (typeof window.checkoutConfig.payment[this.getCode()].url === 'undefined') {
                    return;
                }

                let hostedTokenizationPageUrl = window.checkoutConfig.payment[this.getCode()].url;

                component.tokenizer = new Tokenizer(hostedTokenizationPageUrl, 'div-hosted-tokenization', {
                    hideCardholderName: false
                });

                return component.tokenizer.initialize()
                    .then(() => {
                        // after initialization methods
                    })
                    .catch(reason => {
                        // error handler
                    })
            },
        };
    }
);
