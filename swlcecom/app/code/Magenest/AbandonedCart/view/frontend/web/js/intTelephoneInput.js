/**
 * Created by magenest on 22/02/2019.
 */
define([
    'jquery',
    'intlTelInput'
], function ($) {
    var initIntl = function (config, node) {
        $(node).intlTelInput(config);
    };
    return initIntl;
});