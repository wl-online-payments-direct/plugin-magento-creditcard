define([
    'ko',
    'jquery',
    'mage/storage',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Vault/js/view/payment/vault-enabler',
    'Worldline_CreditCard/js/model/tokenizer',
    'Worldline_CreditCard/js/view/credit-card/create-payment',
    'Worldline_CreditCard/js/view/credit-card/create-surcharge',
    'Worldline_PaymentCore/js/model/device-data',
    'Worldline_PaymentCore/js/model/message-manager',
    'Magento_Ui/js/modal/alert',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/url',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Ui/js/model/messageList',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/payment/additional-validators'
], function (
    ko,
    $,
    storage,
    quote,
    Component,
    VaultEnabler,
    tokenizerAction,
    placeOrderAction,
    surchargeAction,
    deviceData,
    messageManager,
    alert,
    totalsDefaultProvider,
    fullScreenLoader,
    urlBuilder,
    urlBuilderModel,
    globalMessageList,
    priceUtils,
    additionalValidators
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Worldline_CreditCard/payment/worldlinecc',
            isSurchargeEnabled: ko.observable(false),
            actionsVisible: true,

            listens: {
                'checkout.sidebar.place-button:surchargePrevent':'updateActionsBlockVisibility'
            }
        },

        tokenizer: {},

        /**
         * @returns {exports.initialize}
         */
        initialize: function () {
            this._super();

            tokenizerAction.setCode(this.getCode());
            this.isSurchargeEnabled(tokenizerAction.isSurchargeActive());
            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode(this.getVaultCode());

            tokenizerAction.isSurchargeActive.subscribe(function (isActive) {
                this.isSurchargeEnabled(isActive);
            }, this)

            return this;
        },

        initObservable: function () {
            this._super().observe('actionsVisible');

            return this;
        },

        updateActionsBlockVisibility: function (isVisible) {
            this.actionsVisible(isVisible);
        },

        /**
         * Fix for OSC, it's hidden by default with css
         * @param el
         */
        afterToolbarRender: function (el) {
            if (this.actionsVisible() && tokenizerAction.isSurchargeActive()) {
                $(el).css('display', 'block');
            }

            this.actionsVisible.subscribe(function (isVisible) {
                if (isVisible) {
                    $(el).show();
                }
            })
        },

        initializeTokenizer: function () {
            tokenizerAction.initializeTokenizer(
                this,
                'div-hosted-tokenization',
                {
                    hideCardholderName: false
                }
            );
        },

        /**
         * Get payment method code
         */
        getCode: function () {
            return this.item.method;
        },

        /**
         * @param {String|null} hostedTokenizationId
         * @returns {Object}
         */
        getData: function (hostedTokenizationId) {
            let data = this._super();

            if (hostedTokenizationId) {
                let additionalData = deviceData.getData();
                additionalData.hosted_tokenization_id = hostedTokenizationId;
                data.additional_data = additionalData;

                this.vaultEnabler.visitAdditionalData(data);
            }

            return data;
        },

        /**
         * @returns {Boolean}
         */
        isVaultEnabled: function () {
            return this.vaultEnabler.isVaultEnabled();
        },

        /**
         * @returns {String}
         */
        getVaultCode: function () {
            return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
        },

        /**
         * Get list of available CC types
         *
         * @returns {Object}
         */
        getAvailableTypes: function () {
            let availableTypes = window.checkoutConfig.payment[this.getCode()].icons;
            if (availableTypes && availableTypes instanceof Object) {
                return Object.keys(availableTypes);
            }

            return [];
        },

        /**
         * Get payment icons.
         * @param {String} type
         * @returns {Boolean}
         */
        getIcons: function (type) {
            return window.checkoutConfig.payment[this.getCode()].icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment[this.getCode()].icons[type]
                : false;
        },

        getSurcharge: function (data, event) {
            this.placeOrder(data, event);
        },

        placeOrder: function (data, event) {
            let self = this;

            if (event) {
                event.preventDefault();
            }

            if (!this.validate()
                || !additionalValidators.validate()
                || this.isPlaceOrderActionAllowed() !== true) {
                return false;
            }

            this.isPlaceOrderActionAllowed(false);

            if (this.isTokenizerResult) {
                if ((!tokenizerAction.isOscValid() && tokenizerAction.staticSurchargeEnabled )
                    || tokenizerAction.isSurchargeActive()) {
                    this.createSurcharge(this.isTokenizerResult);
                } else {
                    this.createPayment(this.isTokenizerResult);
                }

                return true;
            }

            this.processPlaceOrder();

            return true;
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
            })
            .catch((error) => {
                console.error(error);
            });
        },

        createSurcharge: function (result) {
            let self = this;

            $.when(
                surchargeAction(result.hostedTokenizationId)
            )
            .done(function (response) {
                self.succeedSurcharge(response);
                tokenizerAction.isOscValid(true);
            })
            .fail(function () {
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
