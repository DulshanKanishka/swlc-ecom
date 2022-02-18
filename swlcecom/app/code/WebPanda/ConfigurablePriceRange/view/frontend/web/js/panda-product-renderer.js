define([
    'jquery',
    'underscore',
    'Magento_Catalog/js/price-utils',
    'mage/template',
    "Magento_Swatches/js/swatch-renderer"
], function ($, _, utils, mageTemplate) {
    'use strict';

    $.widget('mage.pandaSwatchRenderer', $.mage.SwatchRenderer, {
        priceOptions: {
            priceTemplate: '<span class="price"><%- data %></span>'
        },

        /**
         * Update total price
         *
         * @private
         */
        _UpdatePrice: function () {
            var $widget = this,
                $product = $widget.element.parents($widget.options.selectorProduct),
                $productPrice = $product.find(this.options.selectorProductPrice),
                options = _.object(_.keys($widget.optionsMap), {}),
                result,
                tierPriceHtml,
                priceRangeFrom = $product.find('[data-price-type="priceRange-from"]'),
                priceRangeTo = $product.find('[data-price-type="priceRange-to"]'),
                priceRangeAll = $product.find('[data-price-type="priceRange-all"]');

            $widget.element.find('.' + $widget.options.classes.attributeClass + '[option-selected]').each(function () {
                var attributeId = $(this).attr('attribute-id');

                options[attributeId] = $(this).attr('option-selected');
            });

            result = $widget.options.jsonConfig.optionPrices[_.findKey($widget.options.jsonConfig.index, options)];

            $productPrice.trigger(
                'updatePrice',
                {
                    'prices': $widget._getPrices(result, $productPrice.priceBox('option').prices)
                }
            );

            if (typeof result != 'undefined' && result.oldPrice.amount !== result.finalPrice.amount) {
                $(this.options.slyOldPriceSelector).show();
            } else {
                $(this.options.slyOldPriceSelector).hide();
            }

            if (typeof result != 'undefined' && result.tierPrices.length) {
                if (this.options.tierPriceTemplate) {
                    tierPriceHtml = mageTemplate(
                        this.options.tierPriceTemplate,
                        {
                            'tierPrices': result.tierPrices,
                            '$t': $t,
                            'currencyFormat': this.options.jsonConfig.currencyFormat,
                            'priceUtils': priceUtils
                        }
                    );
                    $(this.options.tierPriceBlockSelector).html(tierPriceHtml).show();
                }
            } else {
                $(this.options.tierPriceBlockSelector).hide();
            }

            $(this.options.normalPriceLabelSelector).hide();

            _.each($('.' + this.options.classes.attributeOptionsWrapper), function (attribute) {
                if ($(attribute).find('.' + this.options.classes.optionClass + '.selected').length === 0) {
                    if ($(attribute).find('.' + this.options.classes.selectClass).length > 0) {
                        _.each($(attribute).find('.' + this.options.classes.selectClass), function (dropdown) {
                            if ($(dropdown).val() === '0') {
                                $(this.options.normalPriceLabelSelector).show();
                            }
                        }.bind(this));
                    } else {
                        $(this.options.normalPriceLabelSelector).show();
                    }
                }
            }.bind(this));

            // add price range functionality
            if (priceRangeFrom.length && priceRangeTo.length) {
                var products = $widget._CalcProducts(),
                    priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
                    minVal = 9999999,
                    maxVal = 0,
                    priceTemplate = mageTemplate(this.priceOptions.priceTemplate);

                if (products.length) {
                    _.each(products, function (productId) {
                        var amount = parseFloat($widget.options.jsonConfig.optionPrices[productId].finalPrice.amount);
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
        }
    });
});
