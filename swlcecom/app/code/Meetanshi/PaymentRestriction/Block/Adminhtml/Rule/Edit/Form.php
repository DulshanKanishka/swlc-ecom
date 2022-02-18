<?php

namespace Meetanshi\PaymentRestriction\Block\Adminhtml\Rule\Edit;

use Magento\Backend\Block\Widget\Form\Generic;

class Form extends Generic
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('paymentrestriction_edit');
        $this->setTitle(__('Payment Restriction Information'));
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(['data' => ['id' => 'edit_form', 'action' => $this->getUrl('*/*/save'), 'method' => 'post', 'enctype' => 'multipart/form-data',],]);
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
