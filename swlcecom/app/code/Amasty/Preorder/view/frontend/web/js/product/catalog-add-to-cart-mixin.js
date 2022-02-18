define([
    'jquery',
    'mage/translate',
    'jquery/ui'
], function($, $t) {
    "use strict";

    return function (widget) {

        $.widget('mage.catalogAddToCart', widget, {

            disableAddToCartButton: function(form) {
                this.options.addToCartButtonTextDefault = $(form).find(this.options.addToCartButtonSelector).find('span').text();
                this._super(form);
            }
        });

        return $.mage.catalogAddToCart;
    }
});
