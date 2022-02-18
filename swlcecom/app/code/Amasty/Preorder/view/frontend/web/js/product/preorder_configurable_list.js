define([
    "jquery",
    "jquery/ui",
    'Amasty_Preorder/js/product/preorder'

], function($) {
    $.widget('mage.amastyPreorderConfigurable', $.mage.amastyPreorder, {
        options: {
            map: [],
            currentAttributes: {},
            entity: 0
        },

        //observe element update
        _create: function () {
            this._saveOriginal();
            var self = this;

            $(this.options.swatchOpt).click(function () {
              self.update();
            });
        },

        //check element changes
        update: function () {
            var entity = this.options.entity;
            var attributeValue;
            var isChanged = false;
            for (var attributeId in this.options.currentAttributes[entity]) {
                attributeValue = this.options.currentAttributes[entity][attributeId];
                var $element = $(this.options.swatchOpt).children('[attribute-id=' + attributeId + ']');

                if ($element.attr('option-selected') != attributeValue) {
                    isChanged = true;
                    this.options.currentAttributes[entity][attributeId] = $element.attr('option-selected');
                }
            }

            if (isChanged) {
                this.onChangeProductAttributes();
            }
        },

        //compare changed element attribute with preorder element attribute
        onChangeProductAttributes: function () {
            var entity = this.options.entity;
            var currentProductId = false;

            for (var productId in this.options.map) {
                if (!this.options.map.hasOwnProperty(productId)) {
                    break;
                }

                var productInfo = this.options.map[productId];
                currentProductId = productId;
                for (var attributeId in this.options.currentAttributes[entity]) {
                    attributeValue = this.options.currentAttributes[entity][attributeId];

                    if (productInfo.attributes[attributeId] != attributeValue) {
                        currentProductId = false;
                        break;
                    }
                }

                if (currentProductId) {
                    break;
                }
            }

            if (this.options.map[currentProductId]) {
                this.options.addToCartLabel = this.options.map[currentProductId]['cartLabel'];
                this.options.preOrderNote = this.options.map[currentProductId]['note'];
                this.enable();
            } else {
                this.disable();
            }
        }
    });
});
