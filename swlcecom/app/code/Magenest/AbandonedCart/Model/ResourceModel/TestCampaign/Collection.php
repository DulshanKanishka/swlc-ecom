<?php

namespace Magenest\AbandonedCart\Model\ResourceModel\TestCampaign;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'id';

    public function _construct()
    {
        $this->_init('Magenest\AbandonedCart\Model\TestCampaign', 'Magenest\AbandonedCart\Model\ResourceModel\TestCampaign');
    }
}