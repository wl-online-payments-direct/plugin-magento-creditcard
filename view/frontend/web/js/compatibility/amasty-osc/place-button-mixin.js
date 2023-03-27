define([
    'ko'
], function (ko) {
    'use strict';

    var mixin = {
        defaults: {
            template: 'Worldline_CreditCard/compatibility/amasty-osc/place-order',
            surchargePrevent: ko.observable(false),

            imports: {
                isSurchargeEnabled: '${ $.paymentsNamePrefix }worldline_cc:isSurchargeEnabled'
            },
            listens: {
                '${ $.paymentsNamePrefix }worldline_cc:isSurchargeEnabled': 'updatePlaceButtonDisability'
            }
        },

        initObservable: function () {
            this._super().observe([
                'isSurchargeEnabled',
                'surchargePrevent'
            ]);

            return this;
        },

        updatePlaceButtonDisability: function (isSurchargeEnabled) {
            this.isSurchargeEnabled(isSurchargeEnabled);
            this.surchargePrevent(isSurchargeEnabled);
        },

        /**
         * @inheritDoc
         * @param paymentMethod
         */
        updatePlaceOrderButton: function (paymentMethod) {
            var paymentToolbar,
                button,
                isWlCcMethod = paymentMethod.method === 'worldline_cc',
                showSurcharge = isWlCcMethod && this.isSurchargeEnabled();

            if (!paymentMethod) {
                this.visible(true);

                return;
            }

            paymentToolbar = this.getPaymentToolbar(paymentMethod);

            if (paymentToolbar.length === 0 || this.originalToolbarPayments.indexOf(paymentMethod.method) !== -1) {
                this.visible(false);

                return;
            }

            showSurcharge ? this.surchargePrevent(true) : this.surchargePrevent(false);

            if (isWlCcMethod) {
                paymentToolbar = paymentToolbar.filter('.worldline_cc');

                button = paymentToolbar.find(this.placeButtonSelector + ':not(.surcharge)');
            } else {
                if (paymentToolbar.length > 1) {
                    // selector by attribute style should be used instread of :visible,
                    // because some paypal payments can render 2 buttons and thay are both hidden by our css
                    // but not active is hidden by js with attribute style
                    paymentToolbar = paymentToolbar.filter(':not([style*="display: none"])');
                }

                button = paymentToolbar.find(this.placeButtonSelector);
            }

            if (button.length) {
                this.visible(true);
                this.updateLabel(button);
            } else {
                this.visible(false);
            }
        },

    };

    return function (target) {
        return target.extend(mixin);
    };
});
