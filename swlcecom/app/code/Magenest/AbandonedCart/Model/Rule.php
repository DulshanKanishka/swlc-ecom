<?php

namespace Magenest\AbandonedCart\Model;

class Rule extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('Magenest\AbandonedCart\Model\ResourceModel\Rule');
    }

    /**
     * get sms bind to the rule
     */
    public function getSMSData()
    {
        $smsData = json_decode($this->getData('sms_chain'), true);
        return $smsData;
    }

    public function deleteMultiple($dataArr)
    {
        $size = count($dataArr);
        if (!is_array($dataArr) && $size == 0) return;
        $collectionIds = implode(', ', $dataArr);
        $this->getResource()->getConnection()->delete(
            $this->getResource()->getMainTable(),
            "{$this->getIdFieldName()} in ({$collectionIds})"
        );
    }

    public function isValidateTarget($customerGroupId, $websiteId)
    {
        $isValidate       = false;
        $websiteIds       = json_decode($this->getStoresView(), true);
        $customerGroupIds = json_decode($this->getCustomerGroup(), true);

        if ((in_array($websiteId, $websiteIds) || in_array(0, $websiteIds)) && in_array($customerGroupId, $customerGroupIds)) {
            $isValidate = true;
        }

        return $isValidate;
    }
}