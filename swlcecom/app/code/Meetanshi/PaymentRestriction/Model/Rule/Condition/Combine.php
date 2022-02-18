<?php

namespace Meetanshi\PaymentRestriction\Model\Rule\Condition;

use Magento\Rule\Model\Condition\Context;
use Magento\Rule\Model\Condition\Combine as RuleCombine;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject;

class Combine extends RuleCombine
{
    protected $objectManager;
    protected $eventManager = null;

    public function __construct(Context $context, ObjectManagerInterface $objectManager, ManagerInterface $eventManager, array $data = [])
    {

        parent::__construct($context, $data);
        $this->objectManager = $objectManager;
        $this->eventManager = $eventManager;
        $this->setType('Meetanshi\PaymentRestriction\Model\Rule\Condition\Combine');
    }

    public function getNewChildSelectOptions()
    {
        $addressCondition = $this->objectManager->create('Meetanshi\PaymentRestriction\Model\Rule\Condition\Address');
        $addressAttributes = $addressCondition->loadAttributeOptions()->getAttributeOption();

        $attributes = [];
        foreach ($addressAttributes as $code => $label) {
            $attributes[] = ['value' => 'Meetanshi\PaymentRestriction\Model\Rule\Condition\Address|' . $code, 'label' => $label];
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive($conditions, [['value' => 'Meetanshi\PaymentRestriction\Model\Rule\Condition\Product\SubAttributes', 'label' => __('Products subselection')], ['label' => __('Conditions combination'), 'value' => $this->getType()], ['label' => __('Cart Attribute'), 'value' => $attributes],]);

        $additional = new \Magento\Framework\DataObject();
        $this->eventManager->dispatch('salesrule_rule_condition_combine', ['additional' => $additional]);
        $additionalConditions = $additional->getConditions();
        if ($additionalConditions) {
            $conditions = array_merge_recursive($conditions, $additionalConditions);
        }

        return $conditions;
    }

    public function validateNotModel($value)
    {
        if (!$this->getConditions()) {
            return true;
        }

        $all = $this->getAggregator() === 'all';
        $true = (bool)$this->getValue();

        foreach ($this->getConditions() as $condition) {
            if ($value instanceof AbstractModel) {
                $validated = $condition->validate($value);
            } elseif ($value instanceof DataObject && method_exists($condition, 'validateNotModel')) {
                $validated = $condition->validateNotModel($value);
            } elseif ($value instanceof DataObject) {
                $attribute = $value->getData($condition->getAttribute());
                $validated = $condition->validateAttribute($attribute);
            } else {
                $validated = $condition->validateByEntityId($value);
            }
            if ($all && $validated !== $true) {
                return false;
            } elseif (!$all && $validated === $true) {
                return true;
            }
        }
        return $all ? true : false;
    }
}
