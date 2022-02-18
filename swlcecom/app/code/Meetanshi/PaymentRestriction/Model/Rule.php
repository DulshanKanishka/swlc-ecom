<?php

namespace Meetanshi\PaymentRestriction\Model;

use Magento\Rule\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\DataObject;

class Rule extends AbstractModel
{
    protected $objectManager;
    protected $storeManager;

    public function __construct(Context $context, Registry $registry, FormFactory $formFactory, TimezoneInterface $localeDate, StoreManagerInterface $storeManager, ObjectManagerInterface $objectManager, array $data = [])
    {
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        parent::__construct($context, $registry, $formFactory, $localeDate, null, null, $data);
    }

    public function validate(DataObject $object)
    {
        return $this->getConditions()->validateNotModel($object);
    }

    protected function _construct()
    {
        $this->_init('Meetanshi\PaymentRestriction\Model\ResourceModel\Rule');
        parent::_construct();
    }

    public function restrictPayment($method)
    {
        return (false !== strpos($this->getMethods(), ',' . $method->getCode() . ','));
    }

    public function getConditionsInstance()
    {
        return $this->objectManager->create('Meetanshi\PaymentRestriction\Model\Rule\Condition\Combine');
    }

    public function getActionsInstance()
    {
        return $this->objectManager->create('Magento\SalesRule\Model\Rule\Condition\Product\Combine');
    }

    public function massChangeStatus($ids, $status)
    {
        return $this->getResource()->massChangeStatus($ids, $status);
    }

    public function afterSave()
    {
        $ruleProductAttributes = array_merge($this->_getUsedAttributes($this->getConditionsSerialized()), $this->_getUsedAttributes($this->getActionsSerialized()));
        if (count($ruleProductAttributes)) {
            $this->getResource()->saveAttributes($this->getId(), $ruleProductAttributes);
        }

        return parent::afterSave();
    }

    protected function _getUsedAttributes($serializedString)
    {
        $result = [];
        $pattern = '~s:46:"Magento\\\SalesRule\\\Model\\\Rule\\\Condition\\\Product";s:9:"attribute";s:\d+:"(.*?)"~s';
        $matches = [];
        if (preg_match_all($pattern, $serializedString, $matches)) {
            foreach ($matches[1] as $attributeCode) {
                $result[] = $attributeCode;
            }
        }
        return $result;
    }
}
