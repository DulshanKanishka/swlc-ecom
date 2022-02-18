<?php

namespace Magenest\AbandonedCart\Model\ResourceModel;

class TestCampaign extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    public function _construct()
    {
        $this->_init('magenest_abacar_testcampaign', 'id');
    }
}