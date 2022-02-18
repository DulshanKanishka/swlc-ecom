<?php

namespace Meetanshi\PaymentRestriction\Model\ResourceModel\Rule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Meetanshi\PaymentRestriction\Model\Rule', 'Meetanshi\PaymentRestriction\Model\ResourceModel\Rule');
    }

    public function shippingAddressFilter($address)
    {
        $this->addFieldToFilter('is_active', 1);

        $storeId = $address->getQuote()->getStoreId();
        $storeId = (int)$storeId;
        $this->getSelect()->where('stores="" OR stores LIKE "%,' . $storeId . ',%"');

        $groupId = 0;
        if ($address->getQuote()->getCustomerId()) {
            $groupId = $address->getQuote()->getCustomer()->getGroupId();
        }
        $groupId = (int)$groupId;
        $this->getSelect()->where('cust_groups="" OR cust_groups LIKE "%,' . $groupId . ',%"');

        return $this;
    }
}
