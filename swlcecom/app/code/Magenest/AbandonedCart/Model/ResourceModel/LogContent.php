<?php

namespace Magenest\AbandonedCart\Model\ResourceModel;

class LogContent extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('magenest_abacar_log', 'id');
    }
}