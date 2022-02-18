<?php

namespace Magenest\AbandonedCart\Model\ResourceModel\OrderPlaced;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('Magenest\AbandonedCart\Model\OrderPlaced', 'Magenest\AbandonedCart\Model\ResourceModel\OrderPlaced');
    }
}