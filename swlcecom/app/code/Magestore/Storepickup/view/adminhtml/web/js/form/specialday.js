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
    'Magestore_Storepickup/js/utilities',
    'Magestore_Storepickup/js/weekdaytime'
], function($, MAGE_UTIL, WeekdayTime) {
    var openTime = new WeekdayTime(
            '#specialday_time_open_hour',
            '#specialday_time_open_minute'
        ),
        closeTime = new WeekdayTime(
            '#specialday_time_close_hour',
            '#specialday_time_close_minute'
        );

    openTime.callbackChange = function() {
        if(openTime.getStringTime() > closeTime.getStringTime()) {
            closeTime.setWeekdayTime(openTime);
        }
    }

    closeTime.callbackChange = function() {
        if(closeTime.getStringTime() < openTime.getStringTime()) {
            openTime.setWeekdayTime(closeTime);
        }
    }
});
