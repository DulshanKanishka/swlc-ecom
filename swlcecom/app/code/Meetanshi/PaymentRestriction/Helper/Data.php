<?php

namespace Meetanshi\PaymentRestriction\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Registry;
use Magento\Customer\Model\ResourceModel\Group\Collection;

class Data extends AbstractHelper
{
    protected $registry;
    protected $customerGroupCollection;

    public function __construct(Context $context, Collection $customerGroupCollection, Registry $registry)
    {
        $this->customerGroupCollection = $customerGroupCollection;
        $this->registry = $registry;
        parent::__construct($context);
    }

    public function getAllCustomerGroups()
    {
        $customerGroups = $this->customerGroupCollection->load()->toOptionArray();

        $found = false;
        foreach ($customerGroups as $group) {
            if ($group['value'] == 0) {
                $found = true;
            }
        }
        if (!$found) {
            array_unshift($customerGroups, ['value' => 0, 'label' => __('NOT LOGGED IN')]);
        }

        return $customerGroups;
    }

    public function getAllMethods()
    {
        $hash = [];
        foreach ($this->scopeConfig->getValue('payment') as $code => $config) {
            if (!empty($config['title'])) {
                $label = '';
                if (!empty($config['group'])) {
                    $label = ucfirst($config['group']) . ' - ';
                }
                $label .= $config['title'];
                if (!empty($config['allowspecific']) && !empty($config['specificcountry'])) {
                    $label .= ' in ' . $config['specificcountry'];
                }
                $hash[$code] = $label;
            }
        }
        asort($hash);

        $methods = [];
        foreach ($hash as $code => $label) {
            $methods[] = ['value' => $code, 'label' => $label];
        }

        return $methods;
    }

    public function getStatuses()
    {
        return ['1' => __('Active'), '0' => __('Inactive'),];
    }
}
