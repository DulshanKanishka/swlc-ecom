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
    'Magento_Ui/js/grid/columns/actions'
], function ($, $t, _, Actions) {
    'use strict';

    return Actions.extend({
        /**
         * Checks if row has only one visible action.
         *
         * @param {Number} rowIndex - Row index.
         * @returns {Boolean}
         */
        isSingle: function (rowIndex) {
            return true;
        },

        /**
         * Checks if row has more than one visible action.
         *
         * @param {Number} rowIndex - Row index.
         * @returns {Boolean}
         */
        isMultiple: function (rowIndex) {
            return false;
        },

        /**
         * Applies specified action.
         *
         * @param {String} actionIndex - Actions' identifier.
         * @param {Number} rowIndex - Index of a row.
         * @returns {ActionsColumn} Chainable.
         */
        applyAction: function (actionIndex, rowIndex) {
            var action   = this.getAction(rowIndex, actionIndex),
                callback = this._getCallback(action),
                invoiceForm, invoiceFormTitle, shipmentForm, shipmentFormTitle;

            if (typeof action === "undefined") {
                return this;
            }

            switch (action.index){
                case 'mp_create_invoice':
                    invoiceForm      = $('#mp-massorderactions-invoice-form');
                    invoiceFormTitle = $t('Create Invoice');

                    window.mpmassorderactions_Selections = {
                        selected: [action.recordId],
                        filters: {
                            placeholder: true
                        },
                        namespace: 'sales_order_grid',
                        search: ''
                    };
                    this.initPopupForm(invoiceForm, invoiceFormTitle);

                    break;
                case 'mp_create_shipment':
                    shipmentForm      = $('#mp-massorderactions-shipment-form');
                    shipmentFormTitle = $t('Create Shipment');

                    window.mpmassorderactions_Selections = {
                        selected: [action.recordId],
                        filters: {
                            placeholder: true
                        },
                        namespace: 'sales_order_grid',
                        search: ''
                    };
                    this.initPopupForm(shipmentForm, shipmentFormTitle);
                    shipmentForm.find('.mp-load-tracking').removeAttr('disabled');
                    shipmentForm.find('.mp-massorderactions-tracking-wrapper').html('');
                    if (mpIsAutoLoadTracking) {
                        shipmentForm.find('.mp-load-tracking').trigger("click");
                    }

                    break;
                default:
                    action.confirm ?
                        this._confirm(action, callback) :
                        callback();

                    return this;
            }
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
        },

        /**
         * @param action
         *
         * @returns {*}
         * @private
         */
        _getCallback: function (action) {
            if (typeof action === "undefined") {
                return this;
            }

            return this._super();
        }
    });
});
