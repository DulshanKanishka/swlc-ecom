define([
    'jquery',
    'underscore',
    'Magento_Catalog/js/price-utils',
    'mage/template',
    "Magento_ConfigurableProduct/js/configurable"
], function ($, _, utils, mageTemplate) {
    'use strict';

    $.widget('mage.configurable', $.mage.configurable, {
        priceOptions: {
            priceTemplate: '<span class="price"><%- data %></span>'
        },

        /**
         * Reload the price of the configurable product incorporating the prices of all of the
         * configurable product's option selections.
         */
        _reloadPrice: function () {
            $(this.options.priceHolderSelector).trigger('updatePrice', this._getPrices());

            var $widget = this,
                $product = $widget.element.parents($widget.options.selectorProduct),
                priceRangeFrom = $product.find('[data-price-type="priceRange-from"]'),
                priceRangeTo = $product.find('[data-price-type="priceRange-to"]'),
                priceRangeAll = $product.find('[data-price-type="priceRange-all"]');

            // add price range functionality
            if (priceRangeFrom.length && priceRangeTo.length) {
                var products = $widget._CalcProducts(),
                    priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
                    minVal = 9999999,
                    maxVal = 0,
                    priceTemplate = mageTemplate(this.priceOptions.priceTemplate);

                if (products.length) {
                    _.each(products, function (productId) {
                        var amount = parseFloat($widget.options.spConfig.optionPrices[productId].finalPrice.amount);
                        if (amount < minVal) {
                            minVal = amount;
                        }
                        if (amount > maxVal) {
                            maxVal = amount;
                        }
                    });
                } else {
                    minVal = priceRangeFrom.data('price-amount');
                    maxVal = priceRangeTo.data('price-amount');
                }

                if (minVal == maxVal) {
                    var formattedPrice = utils.formatPrice(minVal, priceFormat);

                    priceRangeAll.html(priceTemplate({
                        data: formattedPrice
                    }));
                    $product.find('.price-range-wrapper').hide();
                    $product.find('.price-all-wrapper').show();
                } else {
                    var formattedMinPrice = utils.formatPrice(minVal, priceFormat),
                        formattedMaxPrice = utils.formatPrice(maxVal, priceFormat);

                    priceRangeFrom.html(priceTemplate({
                        data: formattedMinPrice
                    }));
                    priceRangeTo.html(priceTemplate({
                        data: formattedMaxPrice
                    }));
                    $product.find('.price-all-wrapper').hide();
                    $product.find('.price-range-wrapper').show();
                }
            }
        },

        /**
         * Get selected product list
         *
         * @returns {Array}
         * @private
         */
        _CalcProducts: function ($skipAttributeId) {
            var products = [];

            // Generate intersection of products
            _.each(this.options.settings, function (element) {
                var id = $(element).attr('attribute-id'),
                    option = $(element).find('option:selected'),
                    optionId = option.val();

                if (optionId.length == 0) {
                    return;
                }

                if ($skipAttributeId !== undefined && $skipAttributeId === id) {
                    return;
                }

                if (products.length === 0) {
                    products = element.options[element.selectedIndex].config.allowedProducts;
                } else {
                    products = _.intersection(products, element.options[element.selectedIndex].config.allowedProducts);
                }
            });

            return products;
        }
    });
});