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
    'Magestore_Storepickup/js/tag',
], function($) {
    $.widget('magestore.searchbox', {
        options: {
            defaultRaidus: 1,
            distanceUnit: 'Km',
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
                $sliderBar:  $(self.element).find('.slider-range-bar'),
                $sliderAmount: $(self.element).find('.slider-range-amount'),
                $inputAddress: $(self.element).find('.input-search-distance'),
                $listag: $(self.element).find('.list-tag-ul'),
                $inputSearchStoreName: $(self.element).find('.input-search-store-name'),
                $inputSearchCountryId: $(self.element).find('.input-search-country-id'),
                $inputSearchStateId: $(self.element).find('.input-search-state-id'),
                $inputSearchState: $(self.element).find('.input-search-state'),
                $inputSearchCity: $(self.element).find('.input-search-city'),
                $inputSearchZipcode: $(self.element).find('.input-search-zipcode'),
                $btnSearchDistance: $(self.element).find('.btn-search-distance'),
                $btnSearchArea: $(self.element).find('.btn-search-area'),
                $btnResetSearchDistance: $(self.element).find('.btn-reset-search-distance'),
                $btnResetSearchArea: $(self.element).find('.btn-reset-search-area'),
            });

            $(self.element).find('.search-tab').click(function () {
                if(!$(this).hasClass('active')) {
                    var $oldTab = $(self.element).find('.search-tab.active'),
                        $newTab = $(this);

                    $oldTab.removeClass('active');
                    $($oldTab.data('tab-content')).addClass('hide');

                    $newTab.addClass('active');
                    $($newTab.data('tab-content')).removeClass('hide');
                }
            });

            self.$sliderBar.slider({
                range: "min",
                min: 1,
                max: 2000,
                value: options.defaultRaidus,
                slide: function( event, ui ) {
                    self.$sliderAmount.html(ui.value + ' ' + options.distanceUnit);
                },
                change: function( event, ui ) {
                    self.searchDistance();
                },
            });

            self.$btnSearchDistance.click(function () {
                self.searchDistance();
            });

            self.$btnSearchArea.click(function () {
                $(self.element).trigger('search-area');
            });

            self.$btnResetSearchDistance.click(function () {
                self.resetDistance();
                self.$listag.tagManager('unCheckAll');
                $(self.element).trigger('reset-map');
            });

            self.$btnResetSearchArea.click(function () {
                self.resetArea();
                self.$listag.tagManager('unCheckAll');
                $(self.element).trigger('reset-map');
            });
        },

        resetDistance: function () {
            var options = this.options;
            this.$inputAddress.val('');
            this.$sliderBar.slider('value', options.defaultRaidus);
            this.$sliderAmount.html(options.defaultRaidus + ' ' + options.distanceUnit);
        },

        resetArea: function () {
            var options = this.options;
            $('.search-by-area .form-control').val('').trigger('change');
        },

        isActivedTabDistance: function () {
            return   $('.search-tab.search-distance').hasClass('active');
        },

        isActivedTabArea: function () {
            return   $('.search-tab.search-area').hasClass('active');
        },

        searchDistance: function () {
            var self = this, options = this.options;
            if(self.$inputAddress.val()) {
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({
                    'address': self.$inputAddress.val()
                }, function (results, status) {
                    if (status === google.maps.GeocoderStatus.OK) {
                        $(self.element).trigger('geocoded', {location: results[0].geometry.location});
                    }
                });
            }
        },

        getTagIds: function () {
            return this.$listag.tagManager('getTagIds');
        },

        getAreaParams: function () {
            var params = {};
            if(this.$inputSearchStoreName.val()) {
                params.storeName = this.$inputSearchStoreName.val();
            }

            if(this.$inputSearchCountryId.val()) {
                params.countryId = this.$inputSearchCountryId.val();
            }

            if(this.$inputSearchStateId.length) {
                if(this.$inputSearchStateId.css('display') == 'none' && this.$inputSearchState.val()) {
                    params.state = this.$inputSearchState.val();
                } else if(this.$inputSearchState.css('display') == 'none' && this.$inputSearchStateId.val()) {
                    params.state = this.$inputSearchStateId.find('option:selected').html();
                }
            }

            if(this.$inputSearchCity.val()) {
                params.city = this.$inputSearchCity.val();
            }

            if(this.$inputSearchZipcode.val()) {
                params.zipcode = this.$inputSearchZipcode.val();
            }

            return params;
        },

        getRadius: function () {
            var options = this.options, factor;
            if(options.storageUnit[options.distanceUnit]) {
                factor = options.storageUnit[options.distanceUnit].factor;
            } else {
                factor = options.storageUnit['Km'].factor;
            }

            return this.$sliderBar.slider('value') * factor;
        },

        getInputAddress: function () {
            return this.$inputAddress;
        },

        initInputSearchAddess: function () {
            var self = this, options = this.options;
            this.searchAutoComplete = new google.maps.places.SearchBox(this.$inputAddress[0]);
            this.geocoder = new google.maps.Geocoder();
            google.maps.event.addListener(this.searchAutoComplete, 'places_changed', function () {
                self.searchDistance()
            });
        }


    });

    return $.magestore.searchbox;
});
