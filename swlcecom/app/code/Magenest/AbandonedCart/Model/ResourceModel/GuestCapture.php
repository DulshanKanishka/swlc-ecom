<?php

namespace Magenest\AbandonedCart\Model\ResourceModel;

class GuestCapture extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('magenest_abacar_guest_capture', 'id');
    }
}