/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license sliderConfig is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_MassOrderActions
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

require([
    'jquery',
    'mage/translate',
    'uiRegistry'
// eslint-disable-next-line strict
], function ($, $t, registry) {

    mpTrackingInformation = {
        /**
         * Add tracking
         *
         * @param event
         */
        addTrackingInformation: function (event) {
            var form         = $(event.target).closest('form'),
                url          = form.attr('action'),
                orderId      = form.find('input[name="orderId"]').val(),
                number       = form.find('input[name="track[' + orderId + '][number]"]'),
                messageError = $t('This is a required field.');

            form.find('#tracking_number-error').remove();

            if (number.val()) {
                $.ajax({
                    url: url,
                    data: form.serialize(),
                    dataType: 'json',
                    showLoader: true,
                    success: function (result) {
                        /** Reload current grid */
                        var grid   = 'sales_order_grid.sales_order_grid_data_source',
                            target = registry.get(grid);

                        if (target && typeof target === 'object') {
                            target.set('params.t ', Date.now());
                        }

                        $('.mp-massorderactions-message.action-message').html(result.result_html);
                    }
                });
            } else {
                number.parent().append('<label id="tracking_number-error" class="mage-error">' + messageError + '</label>');
            }
        },

        /**
         * Change tracking carrier
         *
         * @param event
         */
        changeCarrier: function (event) {
            var currentValue = $(event.target).val(),
                form         = $(event.target).closest('form'),
                orderId      = form.find('input[name="orderId"]').val(),
                inputTitle   = form.find('input[name="track[' + orderId + '][title]"]'),
                options      = $.parseJSON(form.find('input[name="mp-tracking-carrier-' + orderId + '"]').val());

            inputTitle.val('');
            $.each(options, function (i, data) {
                if (currentValue !== 'custom' && currentValue === data.value) {
                    inputTitle.val(data.label);
                }
            });
        },
    };
});
