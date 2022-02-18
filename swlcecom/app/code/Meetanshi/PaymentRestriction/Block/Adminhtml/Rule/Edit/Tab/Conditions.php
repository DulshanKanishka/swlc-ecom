<?php

namespace Meetanshi\PaymentRestriction\Block\Adminhtml\Rule\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Rule\Block\Conditions as RuleConditions;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;

class Conditions extends Generic implements TabInterface
{
    protected $rendererFieldset;
    protected $conditions;
    protected $registry;
    protected $formFactory;

    public function __construct(Context $context, Registry $registry, FormFactory $formFactory, RuleConditions $conditions, Fieldset $rendererFieldset, array $data)
    {
        $this->rendererFieldset = $rendererFieldset;
        $this->conditions = $conditions;
        $this->registry = $registry;
        $this->formFactory = $formFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function getTabLabel()
    {
        return __('Conditions');
    }

    public function getTabTitle()
    {
        return __('Conditions');
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

        $renderer = $this->rendererFieldset->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')->setNewChildUrl($this->getUrl('*/*/newConditionHtml/form/rule_conditions_fieldset'));

        $fieldset = $form->addFieldset('rule_conditions_fieldset', ['legend' => __('Apply the rule only if the following conditions are met (leave blank for all products).')])->setRenderer($renderer);

        $fieldset->addField('conditions', 'text', ['name' => 'conditions', 'label' => __('Conditions'), 'title' => __('Conditions')])->setRule($model)->setRenderer($this->conditions);

        $form->setValues($model->getData());
        $form->addValues(['id' => $model->getId()]);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
