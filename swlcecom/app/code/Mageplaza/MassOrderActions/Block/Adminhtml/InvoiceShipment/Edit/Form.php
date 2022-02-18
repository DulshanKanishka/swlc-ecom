<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
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

namespace Mageplaza\MassOrderActions\Block\Adminhtml\InvoiceShipment\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Mageplaza\MassOrderActions\Block\Adminhtml\Order\Renderer\Tracking;
use Mageplaza\MassOrderActions\Helper\Data as HelperData;
use Mageplaza\MassOrderActions\Model\Config\Source\System\OrderStatus;

/**
 * Class Form
 * @package Mageplaza\MassOrderActions\Block\Adminhtml\InvoiceShipment\Edit
 */
class Form extends Generic
{
    /**
     * @var OrderStatus
     */
    protected $_orderStatus;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * Form constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param OrderStatus $orderStatus
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        OrderStatus $orderStatus,
        HelperData $helperData,
        array $data = []
    ) {
        $this->_orderStatus = $orderStatus;
        $this->_helperData = $helperData;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return Generic
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'mp_invoice_shipment_edit_form',
                'action' => $this->getUrl(
                    'mpmassorderactions/order/massInvoiceShipment',
                    ['form_key' => $this->getFormKey()]
                ),
                'method' => 'post',
                'enctype' => 'multipart/form-data',
            ],
        ]);

        $defaultStatus = $this->_helperData->getShipmentConfig('default_status');
        $isInvoiceSendEmail = $this->_helperData->getInvoiceConfig('notify_customer');
        $isShipmentSendEmail = $this->_helperData->getShipmentConfig('notify_customer');

        $form->setHtmlIdPrefix('invoice-shipment_');

        $fieldset = $form->addFieldset('base_fieldset', ['class' => 'fieldset-wide']);
        $fieldset->addField('status', 'select', [
            'name' => 'status',
            'label' => __('Change order status to'),
            'title' => __('Change order status to'),
            'values' => $this->_orderStatus->toOptionArray(),
            'value' => $defaultStatus
        ]);

        /** invoice fields */
        $invoiceFieldset = $form->addFieldset('invoice_fieldset', [
            'legend' => __('Invoice'),
            'class' => 'fieldset-wide'
        ]);

        $invoiceFieldset->addField('invoice_send_email', 'checkbox', [
            'name' => 'invoice[send_email]',
            'class' => 'send_email',
            'label' => __('Email Copy of Invoice'),
            'title' => __('Email Copy of Invoice'),
            'checked' => $isInvoiceSendEmail,
            'value' => $isInvoiceSendEmail,
            'onchange' => 'this.value = this.checked ? 1 : 0;mpMassOrderAction.isAppendComment(event);',
        ]);

        $invoiceFieldset->addField('invoice_comment_text', 'textarea', [
            'name' => 'invoice[comment_text]',
            'class' => 'comment_text',
            'label' => __('Invoice Comments'),
            'title' => __('Invoice Comments')
        ]);

        $invoiceFieldset->addField('invoice_comment_customer_notify', 'checkbox', [
            'name' => 'invoice[comment_customer_notify]',
            'class' => 'comment_customer_notify',
            'label' => __('Append Comments'),
            'title' => __('Append Comments'),
            'checked' => false,
            'onchange' => 'this.value = this.checked ? 1 : 0;',
            'disabled' => !$isInvoiceSendEmail
        ]);

        /** shipment fields */
        $shipmentFieldset = $form->addFieldset('shipment_fieldset', [
            'legend' => __('Shipment'),
            'class' => 'fieldset-wide'
        ]);

        $shipmentFieldset->addField('shipment_send_email', 'checkbox', [
            'name' => 'shipment[send_email]',
            'class' => 'send_email',
            'label' => __('Email Copy of Shipment'),
            'title' => __('Email Copy of Shipment'),
            'checked' => $isShipmentSendEmail,
            'value' => $isShipmentSendEmail,
            'onchange' => 'this.value = this.checked ? 1 : 0;mpMassOrderAction.isAppendComment(event);',
        ]);

        $shipmentFieldset->addField('shipment_comment_text', 'textarea', [
            'name' => 'shipment[comment_text]',
            'class' => 'comment_text',
            'label' => __('Shipment Comments'),
            'title' => __('Shipment Comments')
        ]);

        $shipmentFieldset->addField('shipment_comment_customer_notify', 'checkbox', [
            'name' => 'shipment[comment_customer_notify]',
            'class' => 'comment_customer_notify',
            'label' => __('Append Comments'),
            'title' => __('Append Comments'),
            'checked' => false,
            'onchange' => 'this.value = this.checked ? 1 : 0;',
            'disabled' => !$isShipmentSendEmail
        ]);

        $trackingFieldset = $form->addFieldset('tracking_fieldset', [
            'legend' => __('Add Tracking Number'),
            'class' => 'fieldset-wide'
        ]);

        $trackingFieldset->addField('add_tracking', 'note', [])
            ->setAfterElementHtml(
                $this->_helperData->getLoadTrackingHtml($this->getViewFileUrl('images/loader-1.gif'))
            );

        $trackingFieldset->addField('tracking', Tracking::class, ['name' => 'tracking']);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
