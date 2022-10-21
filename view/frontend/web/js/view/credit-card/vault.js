/*browser:true*/
/*global define*/
define([
    'jquery',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Worldline_CreditCard/js/view/credit-card/create-payment',
    'Worldline_PaymentCore/js/model/device-data',
    'Worldline_PaymentCore/js/model/message-manager',
    'Magento_Ui/js/modal/alert',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/url'
], function (
    $,
    VaultComponent,
    placeOrderAction,
    deviceData,
    messageManager,
    alert,
    fullScreenLoader,
    urlBuilder
) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'Worldline_PaymentCore/payment/vault',
            modules: {
                tokenizer: null
            }
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

        placeOrder: function (data, event) {
            let self = this;

            if (event) {
                event.preventDefault();
            }

            if (this.validate() &&
                this.isPlaceOrderActionAllowed() === true
            ) {
                this.isPlaceOrderActionAllowed(false);

                this.tokenizer.submitTokenization()
                    .then((result) => {
                        if (result.success) {
                            self.createPayment(result);
                            return true;
                        }

                        if (result.error) {
                            messageManager.processMessage(result.error.message);
                            self.isPlaceOrderActionAllowed(true);
                        }
                    })
                    .catch((error) => {
                        console.error(error);
                    });

                return true;
            }

            return false;
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
