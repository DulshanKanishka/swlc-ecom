<?php

namespace Magenest\AbandonedCart\Model;

class TestCampaign extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('Magenest\AbandonedCart\Model\ResourceModel\TestCampaign');
    }
}