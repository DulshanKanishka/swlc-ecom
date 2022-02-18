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
    'jquery/ui',
], function($) {
    $.widget('magestore.direction', {
        options: {
        },
        _create: function () {
            var self = this, options = this.options;
            $.extend(this,{
                inputOriginA : $(self.element).find('.originA'),
                inputOriginB : $(self.element).find('.originB'),
            });

            $(self.element).find('.travel').click(function () {
                if(!$(this).hasClass('active')) {
                    $(self.element).find('.travel.active').removeClass('active');
                    $(this).addClass('active');
                    self.triggerEventDirection();
                }
            });

            $(self.element).find('.swap-locations').click(function () {
                if(self.inputOriginA.next().hasClass('originB')) {
                    self.inputOriginA.removeClass('start').addClass('end').insertAfter(self.inputOriginB);
                    self.inputOriginB.removeClass('end').addClass('start');
                } else {
                    self.inputOriginB.removeClass('start').addClass('end').insertAfter(self.inputOriginA);
                    self.inputOriginA.removeClass('end').addClass('start');
                }
                self.triggerEventDirection();
            });

            $(self.element).find('.get-direction').click(function () {
                self.triggerEventDirection();
            });
        },

        setAddress: function (address) {
            $(this.element).find('.origin.originB').val(address);
        },

        triggerEventDirection: function () {
            var self = this, options = this.options;
            var start = $(this.element).find('.origin.start'),
                end = $(this.element).find('.origin.end');
            if(this.inputOriginA.val()) {
                $store = $(this.element).parents('.store-item');
                var latitude = options.latitude ? options.latitude : $store.data('latitude');
                var longitude = options.longitude ? options.longitude : $store.data('longitude');

                var position = new google.maps.LatLng(latitude, longitude);
                $(this.element).trigger('getDirection', {
                    start: start.hasClass('originA') ? start.val() : position,
                    end: end.hasClass('originA') ? end.val() : position,
                    traveMode: $(this.element).find('.travel.active').data('traveling')
                });
            }
        },

        getDirectionPanel: function () {
            return $(this.element).find('.directions-panel')[0];
        }
    });

    return $.magestore.direction;
});
