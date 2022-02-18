<?php

namespace Magenest\AbandonedCart\Model\ResourceModel\AbandonedCart;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'id';

    public function _construct()
    {
        $this->_init('Magenest\AbandonedCart\Model\AbandonedCart', 'Magenest\AbandonedCart\Model\ResourceModel\AbandonedCart');
    }
}