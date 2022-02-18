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

namespace Mageplaza\MassOrderActions\Block\Adminhtml\Invoice\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Mageplaza\MassOrderActions\Helper\Data as HelperData;
use Mageplaza\MassOrderActions\Model\Config\Source\System\OrderStatus;

/**
 * Class Form
 * @package Mageplaza\MassOrderActions\Block\Adminhtml\Invoice\Edit
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
                'id' => 'mp_invoice_edit_form',
                'action' => $this->getUrl(
                    'mpmassorderactions/order/massInvoice',
                    ['form_key' => $this->getFormKey()]
                ),
                'method' => 'post',
                'enctype' => 'multipart/form-data',
            ],
        ]);
        $defaultStatus = $this->_helperData->getInvoiceConfig('default_status');
        $isSendEmail = $this->_helperData->getInvoiceConfig('notify_customer');

        $form->setHtmlIdPrefix('invoice_');
        $form->setFieldNameSuffix('invoice');

        $fieldset = $form->addFieldset('base_fieldset', ['class' => 'fieldset-wide']);
        $fieldset->addField('status', 'select', [
            'name' => 'status',
            'label' => __('Change order status to'),
            'title' => __('Change order status to'),
            'values' => $this->_orderStatus->toOptionArray(),
            'value' => $defaultStatus
        ]);

        $fieldset->addField('send_email', 'checkbox', [
            'name' => 'send_email',
            'class' => 'send_email',
            'label' => __('Email Copy of Invoice'),
            'title' => __('Email Copy of Invoice'),
            'checked' => $isSendEmail,
            'value' => $isSendEmail,
            'onchange' => 'this.value = this.checked ? 1 : 0;mpMassOrderAction.isAppendComment(event);',
        ]);

        $fieldset->addField('comment_text', 'textarea', [
            'name' => 'comment_text',
            'class' => 'comment_text',
            'label' => __('Invoice Comments'),
            'title' => __('Invoice Comments')
        ]);

        $fieldset->addField('comment_customer_notify', 'checkbox', [
            'name' => 'comment_customer_notify',
            'class' => 'comment_customer_notify',
            'label' => __('Append Comments'),
            'title' => __('Append Comments'),
            'checked' => false,
            'onchange' => 'this.value = this.checked ? 1 : 0;',
            'disabled' => !$isSendEmail
        ]);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
