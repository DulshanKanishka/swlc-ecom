<?php
namespace Magebees\Promotionsnotification\Model\ResourceModel\Customer;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magebees\Promotionsnotification\Model\Customer', 'Magebees\Promotionsnotification\Model\ResourceModel\Customer');
    }
}
