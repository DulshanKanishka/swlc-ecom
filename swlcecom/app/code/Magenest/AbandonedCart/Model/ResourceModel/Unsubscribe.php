<?php

namespace Magenest\AbandonedCart\Model\ResourceModel;

class Unsubscribe extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    public function _construct()
    {
        $this->_init('magenest_abacar_unsubscribe', 'id');
    }
}