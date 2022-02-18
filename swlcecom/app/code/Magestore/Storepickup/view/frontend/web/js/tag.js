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
    $.widget('magestore.tagManager', {
        options: {
        },
        _create: function () {
            var self = this, options = this.options;

            $.extend(this, {
                $btnCheckAll: $(this.options.btnCheckAll),
                $btnUnCheckAll: $(this.options.btnUnCheckAll)
            });

            self.$btnCheckAll.click(function () {
                self.checkAll();
                $(self.element).trigger('changeTag', {tagIds: self.getTagIds()});
            });

            self.$btnUnCheckAll.click(function () {
                self.unCheckAll();
                $(self.element).trigger('changeTag', {tagIds: self.getTagIds()});
            });

            $(self.element).find('.tag-icon').click(function () {
                $(this).toggleClass('active');
                $(self.element).trigger('changeTag', {tagIds: self.getTagIds()});
            });
        },
        checkAll: function () {
            $(this.element).find('.tag-icon').addClass('active');
            this.$btnCheckAll.hide();
            this.$btnUnCheckAll.show();
        },
        unCheckAll: function () {
            $(this.element).find('.tag-icon').removeClass('active');
            this.$btnUnCheckAll.hide();
            this.$btnCheckAll.show();
        },
        getTagIds: function () {
            var self = this, tagIds = [];
            $(self.element).find('.tag-icon').each(function (e) {
                if ($(this).hasClass('active')) {
                    tagIds.push($(this).data('tag-id'));
                }
            });

            return tagIds;
        }
    });

    return $.magestore.tagManager;
});
