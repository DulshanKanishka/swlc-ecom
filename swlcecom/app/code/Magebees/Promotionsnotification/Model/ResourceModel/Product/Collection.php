<?php
namespace Magebees\Promotionsnotification\Model\ResourceModel\Product;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magebees\Promotionsnotification\Model\Product', 'Magebees\Promotionsnotification\Model\ResourceModel\Product');
    }
}
