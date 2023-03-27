var config = {
    map: {
        '*': {
            'Amasty_CheckoutStyleSwitcher/template/onepage/place-order.html':
                'Worldline_CreditCard/template/compatibility/amasty-osc/place-order.html',
            'Amasty_CheckoutStyleSwitcher/js/action/start-place-order':
                'Worldline_CreditCard/js/compatibility/amasty-osc/start-place-order'
        }
    },

    config: {
        mixins: {
            'Amasty_CheckoutStyleSwitcher/js/view/place-button': {
                'Worldline_CreditCard/js/compatibility/amasty-osc/place-button-mixin': true
            },
            'Amasty_CheckoutCore/js/view/checkout/summary/item/details': {
                'Worldline_CreditCard/js/compatibility/amasty-osc/view/checkout/summary/item/details-mixin': true
            }
        }
    }
};
