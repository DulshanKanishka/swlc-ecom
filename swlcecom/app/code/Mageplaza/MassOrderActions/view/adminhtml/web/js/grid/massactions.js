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

define([
    'jquery',
    'mage/translate',
    'underscore',
    'Magento_Ui/js/modal/modal'
], function ($, $t, _) {
    'use strict';

    return function (MassActions) {
        return MassActions.extend({
            /**
             * Default action callback. Sends selections data
             * via POST request.
             *
             * @param {Object} action - Action data.
             * @param {Object} data - Selections data.
             */
            defaultCallback: function (action, data) {
                var commentForm,
                    commentFormTitle,
                    invoiceForm,
                    invoiceFormTitle,
                    shipmentForm,
                    shipmentFormTitle,
                    invoiceShipment,
                    invoiceShipmentTitle;

                switch (action.type){
                    case 'mp_order_comment':
                        commentForm      = $('#mp-massorderactions-comment-form');
                        commentFormTitle = $t('Add Order Comments');

                        this.processSelection(data);
                        this.initPopupForm(commentForm, commentFormTitle);

                        break;

                    case 'mp_create_invoice':
                        invoiceForm      = $('#mp-massorderactions-invoice-form');
                        invoiceFormTitle = $t('Create Invoice');

                        this.processSelection(data);
                        this.initPopupForm(invoiceForm, invoiceFormTitle);

                        break;

                    case 'mp_create_shipment':
                        shipmentForm      = $('#mp-massorderactions-shipment-form');
                        shipmentFormTitle = $t('Create Shipment');

                        this.processSelection(data);
                        this.initPopupForm(shipmentForm, shipmentFormTitle);
                        shipmentForm.find('.mp-load-tracking').removeAttr('disabled');
                        shipmentForm.find('.mp-massorderactions-tracking-wrapper').html('');
                        if (mpIsAutoLoadTracking) {
                            shipmentForm.find('.mp-load-tracking').trigger("click");
                        }

                        break;

                    case 'mp_invoice_shipment':
                        invoiceShipment      = $('#mp-massorderactions-invoice-shipment-form');
                        invoiceShipmentTitle = $t('Create Invoice and Shipment');

                        this.processSelection(data);
                        this.initPopupForm(invoiceShipment, invoiceShipmentTitle);
                        invoiceShipment.find('.mp-load-tracking').removeAttr('disabled');
                        invoiceShipment.find('.mp-massorderactions-tracking-wrapper').html('');
                        if (mpIsAutoLoadTracking) {
                            invoiceShipment.find('.mp-load-tracking').trigger("click");
                        }
                        break;

                    default:
                        this._super();
                }
            },

            /**
             *  Get selected records
             *
             *  @param {Object} data - Selections data.
             */
            processSelection: function (data) {
                var itemsType  = data.excludeMode ? 'excluded' : 'selected',
                    selections = {};

                selections[itemsType] = data[itemsType];

                if (!selections[itemsType].length) {
                    selections[itemsType] = false;
                }

                _.extend(selections, data.params || {});
                window.mpmassorderactions_Selections = selections;
            },

            /**
             * Init the popup form function
             */
            initPopupForm: function (formName, title) {
                formName.modal({
                    type: 'slide',
                    title: title,
                    innerScroll: true,
                    modalClass: 'mp-massorderactions-action-box',
                    buttons: []
                });
                formName.trigger('openModal');
                $('.action-select-wrap').removeClass('_active');
                $('.action-select-wrap ul').removeClass('_active');
            }
        });
    };
});
