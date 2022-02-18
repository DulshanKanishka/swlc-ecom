<?php

namespace Meetanshi\PaymentRestriction\Block\Adminhtml\Rule\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Store\Model\System\Store;
use Meetanshi\PaymentRestriction\Helper\Data;

class General extends Generic implements TabInterface
{
    protected $systemStore;
    protected $helper;
    protected $registry;
    protected $formFactory;

    public function __construct(Context $context, Registry $registry, FormFactory $formFactory, Store $systemStore, Data $helper, array $data)
    {
        $this->systemStore = $systemStore;
        $this->helper = $helper;
        $this->registry = $registry;
        $this->formFactory = $formFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function getTabLabel()
    {
        return __('General');
    }

    public function getTabTitle()
    {
        return __('General');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    protected function _prepareForm()
    {
        $model = $this->registry->registry('current_paymentrestriction_rule');

        $form = $this->formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('general', ['legend' => __('General')]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
        $fieldset->addField('name', 'text', ['name' => 'name', 'label' => __('Rule Name'), 'title' => __('Rule Name'), 'required' => true]);

        $fieldset->addField('is_active', 'select', ['label' => __('Status'), 'name' => 'is_active', 'options' => $this->helper->getStatuses(),]);

        $fieldset->addField('methods', 'multiselect', ['label' => __('Payment Methods'), 'name' => 'methods[]', 'values' => $this->helper->getAllMethods(), 'required' => true,]);

        $fieldset->addField('cust_groups', 'multiselect', ['name' => 'cust_groups[]', 'label' => __('Customer Groups'), 'values' => $this->helper->getAllCustomerGroups(), 'note' => __('Leave empty or select all to apply the rule to any group'),]);

        $fieldset->addField('stores', 'multiselect', ['label' => __('Stores'), 'name' => 'stores[]', 'values' => $this->systemStore->getStoreValuesForForm(), 'note' => __('Leave empty or select all to apply the rule to any'),]);

        $form->setValues($model->getData());
        $form->addValues(['id' => $model->getId()]);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
