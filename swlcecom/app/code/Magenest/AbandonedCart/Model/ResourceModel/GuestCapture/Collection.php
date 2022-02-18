<?php

namespace Magenest\AbandonedCart\Model\ResourceModel\GuestCapture;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    public function _construct()
    {
        $this->_init('Magenest\AbandonedCart\Model\GuestCapture', 'Magenest\AbandonedCart\Model\ResourceModel\GuestCapture');
    }
}