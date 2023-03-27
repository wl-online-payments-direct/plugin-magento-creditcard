/*browser:true*/
/*global define*/
define([
    'ko',
    'jquery',
    'Worldline_CreditCard/js/model/tokenizer',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Worldline_CreditCard/js/view/credit-card/create-payment',
    'Worldline_CreditCard/js/view/credit-card/create-surcharge',
    'Worldline_PaymentCore/js/model/device-data',
    'Worldline_PaymentCore/js/model/message-manager',
    'Magento_Ui/js/modal/alert',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Catalog/js/price-utils',
    'mage/url'
], function (
    ko,
    $,
    tokenizerAction,
    VaultComponent,
    placeOrderAction,
    surchargeAction,
    deviceData,
    messageManager,
    alert,
    totalsDefaultProvider,
    quote,
    fullScreenLoader,
    priceUtils,
    urlBuilder
) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'Worldline_PaymentCore/payment/vault',
            isSurchargeEnabled: ko.observable(false),
            actionsVisible: true,

            modules: {
                tokenizer: null
            }
        },

        /**
         * @returns {exports.initialize}
         */
        initialize: function () {
            this._super();

            this.isSurchargeEnabled(tokenizerAction.isSurchargeActive());

            tokenizerAction.isSurchargeActive.subscribe(function (isActive) {
                this.isSurchargeEnabled(isActive);
            }, this);

            return this;
        },

        /**
         * @param {String|null} hostedTokenizationId
         * @returns {Object}
         */
        getData: function (hostedTokenizationId) {
            let data = this._super();

            if (hostedTokenizationId) {
                let additionalData = deviceData.getData();
                additionalData.public_hash = this.public_hash;
                additionalData.hosted_tokenization_id = hostedTokenizationId;
                data.additional_data = additionalData;
            }

            return data;
        },

        /**
         * @returns
         */
        initializeTokenizer: function () {
            let hostedTokenizationPageUrl = window.checkoutConfig.payment.worldline_cc.url;
            this.tokenizer = new Tokenizer(
                hostedTokenizationPageUrl,
                'iframe-' + this.getId(),
                {hideCardholderName: false, hideTokenFields:false},
                this.getToken()
            );
            this.tokenizer.initialize()
                .then(() => {
                    // Do work after initialization, if any
                })
                .catch(reason => {
                    // Handle iFrame load error
                })

            return true;
        },

        /**
         * @returns {String}
         */
        getToken: function () {
            return this.token;
        },

        /**
         * Get last 4 digits of card
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.maskedCC;
        },

        /**
         * Get expiration date
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.details.expirationDate;
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details.type;
        },

        getSurcharge: function (data, event) {
            this.placeOrder(data, event);
        },

        placeOrder: function (data, event) {
            let self = this;

            if (event) {
                event.preventDefault();
            }

            if (this.validate() &&
                this.isPlaceOrderActionAllowed() === true
            ) {
                this.isPlaceOrderActionAllowed(false);


                if (this.isTokenizerResult) {
                    if ((!tokenizerAction.isOscValid() && tokenizerAction.staticSurchargeEnabled)
                        || tokenizerAction.isSurchargeActive()) {
                        this.createSurcharge(this.isTokenizerResult);
                    } else {
                        this.createPayment(this.isTokenizerResult);
                    }

                    return true;
                }

                this.processPlaceOrder();

                return true;
            }

            return false;
        },

        processPlaceOrder: function () {
            let self = this;

            this.tokenizer.submitTokenization().then((result) => {
                if (result.success) {
                    self.isTokenizerResult = result;

                    if (tokenizerAction.isSurchargeActive()) {
                        self.createSurcharge(result);
                    } else {
                        self.createPayment(result);
                    }
                }

                if (result.error) {
                    messageManager.processMessage(result.error.message);
                    self.isPlaceOrderActionAllowed(true);
                }
            }).catch((error) => {
                console.error(error);
            });
        },

        createSurcharge: function (result) {
            let self = this;

            $.when(
                surchargeAction(result.hostedTokenizationId)
            ).done(function (response) {
                self.succeedSurcharge(response);
                tokenizerAction.isOscValid(true);
            }).fail(function () {
                let msg = $.mage.__('Your request couldn\'t be completed, please try again');
                alert({
                    content: msg,
                    actions: {
                        always: function () {
                            $('div-hosted-tokenization').empty();
                            location.reload();
                        }
                    }
                });
            })

            return true;
        },

        succeedSurcharge: function (value) {
            let self = this;
            let formattedPrice = priceUtils.formatPrice(value, quote.getPriceFormat());
            let messageContainer = this.messageContainer || globalMessageList;
            let message = $.mage.__('Surcharge amount: ' + formattedPrice);

            messageContainer.addSuccessMessage({'message': message});

            totalsDefaultProvider.estimateTotals(quote.shippingAddress()).then(function () {
                tokenizerAction.isSurchargeActive(false);
                self.isPlaceOrderActionAllowed(true);
            });
        },

        createPayment: function (result) {
            let self = this;

            $.when(
                placeOrderAction(this.getData(result.hostedTokenizationId), this.messageContainer)
            ).done(
                function (returnUrl) {
                    if (returnUrl) {
                        window.location.replace(returnUrl);
                    } else {
                        fullScreenLoader.startLoader();

                        setTimeout(() => {
                            self.redirectToSuccess(result.hostedTokenizationId);
                        }, 3000)
                    }
                }
            ).fail(
                function () {
                    let msg = $.mage.__('Your payment couldn\'t be completed, please try again');
                    alert({
                        content: msg,
                        actions: {
                            always: function () {
                                $('div-hosted-tokenization').empty();
                                location.reload();
                            }
                        }
                    });
                }
            ).always(
                function () {
                    self.isPlaceOrderActionAllowed(true);
                }
            );

            return true;
        },

        redirectToSuccess: function (hostedTokenizationId) {
            return $.ajax({
                method: "GET",
                url: urlBuilder.build("wl_creditcard/returns/returnUrl"),
                contentType: "application/json",
                data: {
                    hosted_tokenization_id: hostedTokenizationId
                },
            }).done($.proxy(function (data) {
                if (data.url) {
                    window.location.replace(data.url);
                }
            }, this));
        }
    });
});
