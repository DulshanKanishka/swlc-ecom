<?php

namespace Magenest\AbandonedCart\Model\ResourceModel;

class BlackList extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('magenest_abacar_blacklist', 'id');
    }
}