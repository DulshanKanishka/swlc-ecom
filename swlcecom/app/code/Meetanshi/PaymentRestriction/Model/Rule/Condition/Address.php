<?php

namespace Meetanshi\PaymentRestriction\Model\Rule\Condition;

use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Directory\Model\Config\Source\Allregion;
use Magento\Shipping\Model\Config\Source\Allmethods;

class Address extends AbstractCondition
{
    protected $directoryCountry;
    protected $directoryAllregion;
    protected $shippingMethods;

    public function __construct(Context $context, Country $directoryCountry, Allregion $directoryAllregion, Allmethods $shippingMethods, array $data = [])
    {
        parent::__construct($context, $data);
        $this->directoryCountry = $directoryCountry;
        $this->directoryAllregion = $directoryAllregion;
        $this->shippingMethods = $shippingMethods;
    }

    public function loadAttributeOptions()
    {
        parent::loadAttributeOptions();

        $attributes = $this->getAttributeOption();
        unset($attributes['payment_method']);
        $attributes['base_subtotal'] = __('Subtotal');
        $attributes['total_qty'] = __('Total Items Quantity');
        $attributes['weight'] = __('Total Weight');
        $attributes['shipping_method'] = __('Shipping Method');
        $attributes['postcode'] = __('Shipping Postcode');
        $attributes['region'] = __('Shipping Region');
        $attributes['region_id'] = __('Shipping State/Province');
        $attributes['country_id'] = __('Shipping Country');
        $attributes['street'] = __('Address Line');
        $attributes['city'] = __('City');

        $this->setAttributeOption($attributes);

        return $this;
    }

    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);
        return $element;
    }

    public function getInputType()
    {
        switch ($this->getAttribute()) {
            case 'base_subtotal':
            case 'weight':
            case 'total_qty':
                return 'numeric';

            case 'country_id':
            case 'region':
            case 'shipping_method':
                return 'select';
        }
        return 'string';
    }

    public function getValueElementType()
    {
        switch ($this->getAttribute()) {
            case 'country_id':
            case 'region':
            case 'shipping_method':
                return 'select';
        }
        return 'text';
    }

    public function getOperatorSelectOptions()
    {
        $operators = $this->getOperatorOption();
        if ($this->getAttribute() == 'street') {
            $operators = ['{}' => __('contains'), '!{}' => __('does not contain'), '{%' => __('starts from'), '%}' => __('ends with'),];
        }

        $type = $this->getInputType();
        $option = [];
        $operatorByType = $this->getOperatorByInputType();
        foreach ($operators as $key => $val) {
            if (!$operatorByType || in_array($key, $operatorByType[$type])) {
                $option[] = ['value' => $key, 'label' => $val];
            }
        }
        return $option;
    }

    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            switch ($this->getAttribute()) {
                case 'country_id':
                    $options = $this->directoryCountry->toOptionArray();
                    break;

                case 'region':
                    $options = $this->directoryAllregion->toOptionArray();
                    break;

                case 'shipping_method':
                    $options = $this->shippingMethods->toOptionArray();
                    break;

                default:
                    $options = [];
            }
            $this->setData('value_select_options', $options);
        }
        return $this->getData('value_select_options');
    }

    public function getDefaultOperatorInputByType()
    {
        $operator = parent::getDefaultOperatorInputByType();
        $operator['string'][] = '{%';
        $operator['string'][] = '%}';
        return $operator;
    }

    public function getDefaultOperatorOptions()
    {
        $operator = parent::getDefaultOperatorOptions();
        $operator['{%'] = __('starts from');
        $operator['%}'] = __('ends with');

        return $operator;
    }

    public function validateAttribute($attribute)
    {
        if (is_object($attribute)) {
            return false;
        }

        $value = $this->getValueParsed();

        $operator = $this->getOperatorForValidate();

        if ($this->isArrayOperatorType() xor is_array($value)) {
            return false;
        }

        $result = false;
        switch ($operator) {
            case '{%':
                if (!is_scalar($attribute)) {
                    return false;
                } else {
                    $result = substr($attribute, 0, strlen($value)) == $value;
                }
                break;
            case '%}':
                if (!is_scalar($attribute)) {
                    return false;
                } else {
                    $result = substr($attribute, -strlen($value)) == $value;
                }
                break;
            default:
                return parent::validateAttribute($attribute);
                break;
        }
        return $result;
    }

    protected function _isArrayOperatorType()
    {
        $result = false;
        if (method_exists($this, 'isArrayOperatorType')) {
            $result = $this->isArrayOperatorType();
        } else {
            $operator = $this->getOperator();
            $result = ($operator === '()' || $operator === '!()');
        }
        return $result;
    }
}
