<?php
namespace Magebees\Promotionsnotification\Model\ResourceModel\Store;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magebees\Promotionsnotification\Model\Store', 'Magebees\Promotionsnotification\Model\ResourceModel\Store');
    }
}
