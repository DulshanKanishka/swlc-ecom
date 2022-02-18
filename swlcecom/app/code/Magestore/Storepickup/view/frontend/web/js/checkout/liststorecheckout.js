/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
define([
    'jquery',
    'mage/template',
    'mage/url',
    'mage/translate',
    'jquery/ui',
    'Magestore_Storepickup/js/direction'
], function ($, mageTemplate, url, $t) {
    $.widget('magestore.liststore', {
        options: {
            storeTemplate: '#store-template',
            defaultStoreIcon: '',
            mediaUrlImage: '',
            storageUnit: {
                M: {
                    label: 'M',
                    factor: 1
                },
                Km: {
                    label: 'Km',
                    factor: 1000
                },
                Mi: {
                    label: 'Mi',
                    factor: 1609.34
                }
            },
        },
        _create: function () {
            var self = this, options = this.options;

            $.extend(this, {
                stores: [],
                $directionBox: $('.option-direction'),
            });

            self.storeTmpl = mageTemplate($(options.storeTemplate).html());
        },

        addStore: function (store) {
            var self = this, options = this.options;
            $(self.element).append(self.toStoreHtml(store));
            $(self.element).trigger('addStore', store);
        },

        toStoreHtml: function (store) {
            var self = this, options = this.options;
            store.imgSrc = store.baseimage ? options.mediaUrlImage + store.baseimage : options.defaultStoreIcon;

            if (store.distance) {
                var distance = parseFloat(store.distance) / options.storageUnit[options.distanceUnit].factor;
                store.distanceText = distance.toFixed(1) + ' ' + options.distanceUnit;
            }
            var $store = $(self.storeTmpl({data: store}).trim());
            return $store;
        },

        updateListStoresEvent: function () {
            var self = this, options = this.options;
            $(self.element).find('.store-item').click(function () {
                if (!$(this).hasClass('store-active')) {
                    $(self.element).find('.store-item.store-active').removeClass('store-active');
                    $(this).addClass('store-active');
                }
                $(self.element).trigger('clickStore', {index: $(this).data('store-index')});
            });

            $(self.element).find('.btn-link.direction').click(function () {
                var $store = $(this).parents('.store-item');
                self.$directionBox.direction('setAddress', $store.data('address'));
                if ($store.find('.option-direction').length) {
                    if (self.$directionBox.css('display') === "none") {
                        self.$directionBox.show();
                    } else {
                        self.$directionBox.hide();
                    }
                } else {
                    $store.append(self.$directionBox);
                    self.$directionBox.find('.directions-panel').html('');
                    self.$directionBox.show();
                }
            });

            $(self.element).find('.btn-link.street-view').click(function () {
                var $store = $(this).parents('.store-item');
                $(self.element).trigger('streetview', {index: $store.data('store-index')});
            });
        },

        addEventDirectionBox: function (storeItem) {
            var self = this, options = this.options;
            storeItem.find('.btn-link.direction').click(function () {
                var $store = $(this).parents('.store-item');
                self.$directionBox.direction('setAddress', $store.data('address'));
                if ($store.find('.option-direction').length) {
                    if (self.$directionBox.css('display') === "none") {
                        self.$directionBox.show();
                    } else {
                        self.$directionBox.hide();
                    }
                } else {
                    $store.append(self.$directionBox);
                    self.$directionBox.find('.directions-panel').html('');
                    self.$directionBox.show();
                }
            });
            storeItem.find('.btn-link.apply').click(function () {
                var $store = $(this).parents('.store-item');
                $('#popup-mpdal').modal('closeModal');
                $('.list-store-select').val($store.data('store-id')).trigger('change');

            });
            storeItem.find('.btn-link.street-view').click(function () {
                var $store = $(this).parents('.store-item');
                $(self.element).trigger('streetview', {index: $store.data('store-index')});
            });

        },

        getDirectionBox: function () {
            return this.$directionBox;
        },

        updateStores: function (storesjson) {
            var self = this, options = this.options;
            $(self.element).html('');

            $.each(storesjson, function (index, store) {
                store.index = index;
                self.addStore(store);
            });

            self.updateListStoresEvent();

            this.stores = storesjson;
        }
    });

    return $.magestore.liststore;
});
