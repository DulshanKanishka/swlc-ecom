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
    'Magestore_Storepickup/js/store/map/map-loader',
    'mage/template',
    'Magestore_Storepickup/js/direction',
    'jquery/ui',
    'mage/translate'
], function($, Maploader, mageTemplate) {
    'use strict';

    $.widget('magestore.Map', {
        options: {
            urlLoadStore: '',
            defaultStoreIcon: '',
            mediaUrlImage: ''
        },
        _create: function() {
            var self= this, options  = this.options;
            this._initOption();

            $.extend(this, {
                $directionBox: $('.option-direction'),
                stores: []
            });

            Maploader.done($.proxy(this._initMap, this)).fail(function() {
                console.error("ERROR: Google maps library failed to load");
            });
        },

        _initOption: function () {
            this._defaultOption('latitude',0);
            this._defaultOption('longitude',0);
            this._defaultOption('zoom_level',4);
            this._defaultOption('minZoom',1);
            this._defaultOption('maxZoom',20);
        },

        _defaultOption:  function(name, defaultValue) {
            if(this.options[name] === '' || this.options[name] === null || typeof this.options[name] === 'undefined') {
                this.options[name] = defaultValue;
            }
        },

        _initMap: function() {
            var self= this, options  = this.options,
                centerPosition = new google.maps.LatLng(options.latitude, options.longitude);

            /**
             * map
             * @type {google.maps.Map}
             */
            this.map = new google.maps.Map(this.element[0], {
                zoom: parseFloat(options.zoom_level),
                center: centerPosition,
                minZoom: options.minZoom,
                maxZoom: options.maxZoom
            });

            this.storePopupTmpl = mageTemplate($(options.storePopupTemplate).html());

            /**
             * infor windopw
             * @type {google.maps.InfoWindow}
             */
            this.infowindow = new google.maps.InfoWindow({
                //maxWidth: 250,
                //disableAutoPan: true,
                maxWidth: 450,
                minWidth: 350,
            });

            /**
             * Directions Service
             * @type {google.maps.DirectionsService}
             */
            this.dirService = new google.maps.DirectionsService();

            /**
             * Directions Renderer
             * @type {google.maps.DirectionsRenderer}
             */
            this.dirDisplay = new google.maps.DirectionsRenderer({
                draggable: true,
                map: this.map
            });

            /**
             * Street view
             * @type {google.maps.StreetViewService}
             */
            this.streetViews = new google.maps.StreetViewService();

            /**
             * Street View Panorama
             * @type {google.maps.StreetViewPanorama}
             */
            this.panorama = new google.maps.StreetViewPanorama(this.element[0], {
                enableCloseButton: true,
                visible: false
            });

            var optionMarker = {
                map: this.map,
                position: new google.maps.LatLng(options.latitude, options.longitude),
            }

            if(options.marker_icon) {
                optionMarker.icon = options.mediaUrlImage + options.marker_icon;
            }

            options.imgSrc = options.baseimage ? options.mediaUrlImage + options.baseimage : options.defaultStoreIcon;
            var $store = $(self.storePopupTmpl({data: options}).trim());

            this.markerStore = new google.maps.Marker(optionMarker);

            var $store = $(self.storePopupTmpl({data: options}).trim());
            self.infowindow.setContent($store[0]);
            self.infowindow.open(self.map, self.markerStore);
            self.map.panTo(this.markerStore.getPosition());
            self.map.setZoom(parseFloat(options.zoom_level));

            google.maps.event.addListener( this.markerStore, 'click', function () {
                self.infowindow.open(self.map, self.markerStore);
                self.map.panTo( self.markerStore.getPosition());
                self.map.setZoom(parseFloat(options.zoom_level));
            });

            $('.location-box-view').show();
            this.map.controls[google.maps.ControlPosition.LEFT_TOP].push($('.location-box-view')[0]);

            $('.location-box-view').click(this.currentPosition.bind(this));

            /**
             * Event listener
             */

            var autocomplete = new google.maps.places.Autocomplete(this.$directionBox.find('.origin.originA')[0]);
            google.maps.event.addListener(autocomplete, 'place_changed', function () {
                self.$directionBox.direction('triggerEventDirection');
            });

            this.$directionBox.on('getDirection', function (event, data) {
                self.getDirection(data.start, data.end, data.traveMode, self.$directionBox.direction('getDirectionPanel'));
            });
        },

        getDirection: function(start, end, travelMode, panelElement) {
            var self= this, options  = this.options;
            this.dirDisplay.setMap(this.map);
            this.dirDisplay.setPanel(panelElement);
            this.dirService.route({
                origin: start,
                destination: end,
                travelMode: google.maps.TravelMode[travelMode],
                unitSystem: (options.distanceUnit == 'Km') ? google.maps.UnitSystem.METRIC : google.maps.UnitSystem.IMPERIAL
            }, function(response, status) {
                if (status === google.maps.DirectionsStatus.OK) {
                    this.dirDisplay.setDirections(response);
                } else {
                    self.dirDisplay.setMap(null);
                    self.dirDisplay.setPanel(null);
                    alert($.mage.__('At least one of the origin, destination, or waypoints could not be geocoded.'));
                }
            }.bind(this));
        },

        streetView: function(marker) {
            this.streetViews.getPanorama({
                location: marker.getPosition(),
                radius: 50
            }, this.processSVData.bind(this));
        },

        processSVData: function processSVData(data, status) {
            this.panorama.setVisible(false);
            if (status === google.maps.StreetViewStatus.OK) {
                this.panorama.setPano(data.location.pano);
                this.panorama.setPov({
                    heading: 270,
                    pitch: 0
                });
                this.panorama.setVisible(true);
            } else {
                window.alert($.mage.__('Street Not Found !'));
            }
        },

        getMap: function () {
            return this.map;
        },

        currentPosition: function() {
            var self= this, options  = this.options;
            var infoPopup = new google.maps.InfoWindow({
                content: "",
                maxWidth: 293
            });

            var geocoder = new google.maps.Geocoder();
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    var latlng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                    infoPopup.setPosition(pos);
                    geocoder.geocode({
                        latLng: latlng
                    }, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            infoPopup.setContent(results[0]['formatted_address']);
                        }
                    });
                    infoPopup.setMap(self.map);
                    self.map.setZoom(13);
                    self.map.setCenter(pos);
                }), function() {
                    infoPopup.setPosition(self.map.getCenter());
                    infoPopup.setContent($.mage.__('Error: The Geolocation service failed.'));
                };
            }
        }

    });

    return $.magestore.Map;
});
