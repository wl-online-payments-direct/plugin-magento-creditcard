define([
    'jquery',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals'
], function ($, Component, quote, totals) {
    'use strict';

    var NAME_SEGMENT = 'worldline_payment_surcharging';

    return Component.extend({
        totals: quote.getTotals(),

        /**
         * @return {String}
         */
        getValue: function () {
            var price = 0;

            if (this.totals()) {
                price = totals.getSegment(NAME_SEGMENT).value;
            }

            return this.getFormattedPrice(price);
        },

        /**
         * @returns {Boolean}
         */
        isDisplayed: function () {
            var creditCardSegmentSurcharging = totals.getSegment(NAME_SEGMENT);

            if (this.totals() && creditCardSegmentSurcharging !== null && creditCardSegmentSurcharging.value > 0) {
                return true;
            }

            return false;
        },
    });
});
