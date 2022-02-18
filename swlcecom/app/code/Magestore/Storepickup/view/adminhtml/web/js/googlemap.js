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
    'Magestore_Storepickup/js/pagination',
    'Magestore_Storepickup/js/liststore',
    'Magestore_Storepickup/js/searchbox',
    'Magestore_Storepickup/js/direction',
    'Magestore_Storepickup/js/tag',
    'jquery/ui',
    'mage/translate'
], function($, Maploader, mageTemplate) {
    'use strict';

    $.widget('magestore.GoogleMap', {
        options: {
            urlLoadStore: '',
            defaultStoreIcon: '',
            mediaUrlImage: ''
        },
        _create: function() {
            var self= this, options  = this.options;
            this._initOption();

            $.extend(this, {
                $liststoreBox: $(options.liststoreContainer),
                $paginationWrapper: $(options.paginationWrapper),
                $listTag: $(options.listTag),
                $searchBox: $(options.searchBox),
                $directionBox: $('.option-direction'),
                stores: []
            });

            //Maploader.done($.proxy(this._initMap, this)).fail(function() {
            //    console.error("ERROR: Google maps library failed to load");
            //});
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

            /**
             * marker cluster
             * @type {*|k}
             */
            this.markerClusterer = new MarkerClusterer(this.map, [], {
                gridSize: 10,
                maxZoom: 15
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

            this.circleMarker = new google.maps.Marker({
                icon: options.circleCenterIcon
            });

            /**
             * circle
             * @type {google.maps.Circle}
             */
            this.circle = new google.maps.Circle({
                center: null,
                map: null,
                radius: 0,
                fillColor: '#cd003a',
                fillOpacity: 0.1,
                strokeColor: "#000000",
                strokeOpacity: 0.3,
                strokeWeight: 1
            });

            this.$searchBox.searchbox({
                defaultRaidus: options.defaultRaidus,
                distanceUnit: options.distanceUnit
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

            this.$searchBox.searchbox('initInputSearchAddess');
            /**
             * Event listener
             */

            this.$searchBox.on('geocoded', function (event, data) {
                self.drawCycle(data.location, self.$searchBox.searchbox('getRadius'));
                var params = $.extend(self.getParams(), {
                    radius: self.$searchBox.searchbox('getRadius'),
                    latitude: data.location.lat(),
                    longitude: data.location.lng(),
                });
                self.loadStore(params);
            });

            this.$searchBox.on('search-area', function (event, data) {
                self.loadStore(self.getParams());
            });

            this.$searchBox.on('reset-map', function (event, data) {
                self.loadStore({curPage: 1});
                self.circle.setMap(null);
            });

            var autocomplete = new google.maps.places.Autocomplete(this.$directionBox.find('.origin.originA')[0]);
            google.maps.event.addListener(autocomplete, 'place_changed', function () {
                self.$directionBox.direction('triggerEventDirection');
            });

            this.$directionBox.on('getDirection', function (event, data) {
                self.getDirection(data.start, data.end, data.traveMode, self.$directionBox.direction('getDirectionPanel'));
            });

            this.$liststoreBox.on('clickStore', function (event, data) {
                var index = data.index, store = self.stores[index];
                if(store) {
                    google.maps.event.trigger(store.marker, 'click');
                }
            });

            this.$liststoreBox.on('streetview', function (event, data) {
                var index = data.index, store = self.stores[index];
                if (store) {
                    self.streetView(store.marker);
                }
            });

            this.$paginationWrapper.on('changePage', function (event, data) {
                self.$directionBox.hide();
                var params = $.extend(self.getParams(), {
                    curPage: data.newPage
                });
                self.loadStore(params);
            });

            this.$listTag.on('changeTag', function (event, data) {
                var params = self.getParams();
                self.loadStore(params);
            });

            $('.location-box-view').show();
            this.map.controls[google.maps.ControlPosition.LEFT_TOP].push($('.location-box-view')[0]);

            $('.location-box-view').click(this.currentPosition.bind(this));

            this.loadStore({curPage: 1});
        },

        getParams: function () {
            var params = {
                tagIds: this.$searchBox.searchbox('getTagIds')
            };

            if(this.$searchBox.searchbox('isActivedTabDistance')) {
                this.$searchBox.searchbox('resetArea');
                if(this.circle.getMap()) {
                    params.radius = this.$searchBox.searchbox('getRadius');
                    params.latitude = this.circle.getCenter().lat();
                    params.longitude = this.circle.getCenter().lng();
                }
            }else if(this.$searchBox.searchbox('isActivedTabArea')) {
                $.extend(params, this.$searchBox.searchbox('getAreaParams'));
                this.$searchBox.searchbox('resetDistance');
                this.removeCycle();
            }

            return params;
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

        drawCycle: function(center, radius) {
            this.removeCycle();
            this.circleMarker.setPosition(center);
            this.circleMarker.setMap(this.map);
            this.circle.setMap(this.map);
            this.circle.setRadius(radius);
            this.circle.bindTo('center', this.circleMarker, 'position');
            this.map.setCenter(center);
            this.map.setZoom(Math.round(15 - Math.log(radius / 1000) / Math.LN2));
        },

        removeCycle: function() {
            if (this.circle.getMap()) {
                this.circleMarker.setMap(null);
                this.circle.setMap(null);
            }
        },

        loadStore: function (params) {
            var self= this, options  = this.options;
            $(options.loader).show();
            $.ajax({
                url: this.options.urlLoadStore,
                type: 'POST',
                dataType: 'json',
                data: params,
            }).done(function(data) {

                if (data.pagination) {
                    self.$paginationWrapper.html(data.pagination);
                    self.$paginationWrapper.pagination('addChangePageEvent');
                }

                if (data.storesjson) {
                    self.stores = data.storesjson;
                    self.markerClusterer.clearMarkers();
                    self.$directionBox.insertAfter('.boxes-content');
                    self.$liststoreBox.html('');
                    var bounds = new google.maps.LatLngBounds();
                    $.each(self.stores, function (index, store) {
                        store.index = index;
                        self.$liststoreBox.liststore('addStore', store);

                        var optionMarker = {
                            map: null,
                            position: new google.maps.LatLng(store.latitude, store.longitude),
                        }

                        if(store.marker_icon) {
                            optionMarker.icon = options.mediaUrlImage + store.marker_icon;
                        }

                        store.marker = new google.maps.Marker(optionMarker);

                        google.maps.event.addListener(store.marker, 'click', function () {
                            self.map.panTo(store.marker.getPosition());
                            var $store = $(self.storePopupTmpl({data: store}).trim());
                            self.$liststoreBox.liststore('addEventDirectionBox',$store);
                            self.infowindow.setContent($store[0]);
                            self.infowindow.setPosition(store.marker.getPosition());
                            self.infowindow.open(self.map, store.marker);
                            self.map.setZoom(parseFloat(store.zoom_level));

                            if(self.panorama.getVisible()) {
                                self.streetView(store.marker);
                            }
                        });

                        self.markerClusterer.addMarker(store.marker);
                        bounds.extend(optionMarker.position);
                    });
                    if (!self.circle.getMap()) {
                        self.map.fitBounds(bounds);
                    }
                    window.markerClusterer = self.markerClusterer;
                    self.$liststoreBox.liststore('updateListStoresEvent');
                    $('.number-store').html(data.num_store +' '+ $.mage.__('Stores'));
                }

                $(options.loader).hide();

            }).fail(function() {
                $(options.loader).hide();
            });
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
                            $('.input-search-distance').val(results[0]['formatted_address']);
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

    return $.magestore.GoogleMap;
});
