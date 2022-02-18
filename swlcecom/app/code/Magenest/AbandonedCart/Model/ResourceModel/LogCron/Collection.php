<?php

namespace Magenest\AbandonedCart\Model\ResourceModel\LogCron;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'id';

    public function _construct()
    {
        $this->_init('Magenest\AbandonedCart\Model\LogCron', 'Magenest\AbandonedCart\Model\ResourceModel\LogCron');
    }
}