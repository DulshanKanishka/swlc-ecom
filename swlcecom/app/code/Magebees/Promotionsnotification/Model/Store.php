<?php
namespace Magebees\Promotionsnotification\Model;

class Store extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magebees\Promotionsnotification\Model\ResourceModel\Store');
    }
}
