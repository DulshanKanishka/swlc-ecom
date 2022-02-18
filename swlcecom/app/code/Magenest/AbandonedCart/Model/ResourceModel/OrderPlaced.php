<?php

namespace Magenest\AbandonedCart\Model\ResourceModel;

class OrderPlaced extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('magenest_order_placed', 'id');
    }
}