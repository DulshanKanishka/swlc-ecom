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
    $.widget('magestore.pagination', {
        options: {
        },
        _create: function () {
            var self = this, options = this.options;
            self.addChangePageEvent();
        },

        addChangePageEvent: function () {
            var self = this, options = this.options;
            $(self.element).find('.page-item:not(.disabled)').click(function () {
                if($(this).data('first-page')) {
                    $(self.element).trigger('changePage', {newPage: $(this).data('first-page')});
                } else if($(this).data('last-page')) {
                    $(self.element).trigger('changePage', {newPage: $(this).data('last-page')});
                } else if(!$(this).hasClass('active')) {
                    $(self.element).trigger('changePage', {newPage: $(this).data('page-id')});
                }
            });
        },

        getCurPage: function () {
            return $(this).find('.page-item.active').data('page-id');
        },
    });

    return $.magestore.pagination;
});
