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
define(['jquery'], function($) {
    var google_maps_loaded_def = null;
    if (!google_maps_loaded_def) {

        google_maps_loaded_def = $.Deferred();

        window.google_maps_loaded = function() {
            google_maps_loaded_def.resolve(google.maps);
        }

        require(['https://maps.googleapis.com/maps/api/js?key='+window.googleMapApiKey+'&sensor=false&callback=google_maps_loaded&libraries=places,geometry'], function() {}, function(err) {
            google_maps_loaded_def.reject();
        });

    }

    return google_maps_loaded_def.promise();

});
