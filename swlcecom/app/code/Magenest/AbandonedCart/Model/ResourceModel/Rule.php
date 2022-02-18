<?php

namespace Magenest\AbandonedCart\Model\ResourceModel;

class Rule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('magenest_abacar_rule', 'id');
    }
}