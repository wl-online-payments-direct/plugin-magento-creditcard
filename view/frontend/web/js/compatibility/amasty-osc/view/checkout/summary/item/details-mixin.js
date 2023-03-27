define([
    'ko',
    'jquery',
    'Worldline_CreditCard/js/compatibility/amasty-osc/action/update-item'
], function (ko, $, updateItemAction) {
    'use strict';

    var mixin = {
        defaults: {
            template: 'Worldline_CreditCard/compatibility/amasty-osc/checkout/summary/item/details',
        },

        /**
         * @inheritDoc
         * @param {object} item
         */
        initOptions: function (item) {
            var itemConfig = this.getItemConfig(item),
                containerSelector = '[data-role="product-attributes"][data-item-id=' + item.item_id + ']',
                container = $(containerSelector);

            if (itemConfig.hasOwnProperty('configurableAttributes')) {
                container.amcheckoutConfigurable({
                    spConfig: JSON.parse(itemConfig.configurableAttributes.spConfig),
                    superSelector: containerSelector + ' .super-attribute-select'
                });
            }

            if (itemConfig.hasOwnProperty('customOptions')) {
                container.priceOptions({
                    optionConfig: JSON.parse(itemConfig.customOptions.optionConfig)
                });
            }

            item.isUpdated = ko.observable(false);

            container.find('input, select, textarea').change(function () {
                item.isUpdated(true);
            });
        },

        /**
         * @inheritDoc
         * @param {html} item
         */
        updateItem: function (item) {
            let form = $(item).is('form') ? $(item) : $(item).parents('form').first();

            if (form.validation().valid()) {
                updateItemAction(form.attr('data-item-id'), form.serialize());
            }
        },

        /**
         * Automatically updates the order form if it`s changed
         *
         * @param {html} item
         * @return {*}
         */
        updateItemAuto: function (item) {
            var isNotEmpty = true,
                options;

            options = $(item).serializeArray();

            $.each(options, function () {
                if (this.value === '') {
                    isNotEmpty = false;
                }
            });

            if (isNotEmpty) {
               if ($(item).validation().valid()) {
                   updateItemAction($(item).attr('data-item-id'), $(item).serialize());
               }
            }
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
