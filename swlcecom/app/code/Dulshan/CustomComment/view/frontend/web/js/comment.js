define([
        "jquery",
        "mage/url",
        'ko',
        'uiComponent',
        'Magento_Ui/js/modal/alert',
        "jquery/ui",
        "mage/translate",
        "mage/mage",
        "mage/validation"
    ], function ($, url, ko, Component, alert, mage) {
        "use strict";

        return Component.extend({
            defaults: {
                msgSaved: false,
                template: 'Dulshan_CustomComment/comment',
            },
            /** Initialize observable properties */
            initObservable: function () {
                this._super()
                    .observe('msgSaved')
                ;
                this.msg = ko.observable('');
                this.getComment();
                return this;
            },

            getComment : function () {
                var self = this;
                var custom_url = url.build('comment/index/index');
                $.ajax({
                    url: custom_url,
                    dataType: 'json',

                    /**
                     * Success callback.
                     * @param {Object} resp
                     * @returns {Boolean}
                     */
                    success: function (resp) {
                        if (typeof resp.comment != 'undefined') {
                            self.msg (resp.comment);
                        }
                    },
                });
            },


            /**
             * Validate feedback form
             */
            validateForm: function () {
                var form = '#feedback-form';
                return $(form).validation() && $(form).validation('isValid');
            },
            submitFeedback: function () {
                if (!this.validateForm()) {
                    return;
                }
                var data = {'message':this.msg(),'status':0};
                var custom_url = url.build('comment/index/index');

                $.ajax({
                    url: custom_url,
                    data: data,
                    type: 'post',
                    dataType: 'json',
                    context: this,
                    beforeSend: this._ajaxBeforeSend,
                    success: function (response) {
                        this.msgSaved(true);
                        // alert({
                        //     content: $.mage.__('Thanks for your comment.')
                        // });
                    },
                    complete: this._ajaxComplete
                });
            }
        });
    }
);
