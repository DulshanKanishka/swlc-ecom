<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\Rule\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    public function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' =>
                    [
                        'id'      => 'edit_form',
                        'action'  => $this->getData('action'),
                        'method'  => 'post',
                        'enctype' => 'multipart/form-data'
                    ]
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}