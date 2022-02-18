<?php

namespace Meetanshi\PaymentRestriction\Block\Adminhtml\Rule;

use Magento\Backend\Block\Widget\Form\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

class Edit extends Container
{
    protected $registry;

    public function __construct(Context $context, Registry $registry, array $data = [])
    {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_rule';
        $this->_blockGroup = 'Meetanshi_PaymentRestriction';

        parent::_construct();

        $this->buttonList->add('save_and_continue_edit', ['class' => 'save', 'label' => __('Save and Continue Edit'), 'data_attribute' => ['mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],]], 10);
    }

    public function getHeaderText()
    {
        $model = $this->registry->registry('current_paymentrestriction_rule');
        if ($model->getId()) {
            $title = __('Edit Payment Restriction Rule `%1`', $model->getName());
        } else {
            $title = __("Add new Payment Restriction Rule");
        }
        return $title;
    }
}
