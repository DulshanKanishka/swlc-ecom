<?php

namespace Magenest\AbandonedCart\Model;

class Unsubscribe extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('Magenest\AbandonedCart\Model\ResourceModel\Unsubscribe');
    }
}