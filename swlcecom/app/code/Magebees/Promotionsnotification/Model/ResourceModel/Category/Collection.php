<?php
namespace Magebees\Promotionsnotification\Model\ResourceModel\Category;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magebees\Promotionsnotification\Model\Category', 'Magebees\Promotionsnotification\Model\ResourceModel\Category');
    }
}
