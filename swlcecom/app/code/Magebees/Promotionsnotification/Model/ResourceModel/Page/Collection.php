<?php
namespace Magebees\Promotionsnotification\Model\ResourceModel\Page;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magebees\Promotionsnotification\Model\Page', 'Magebees\Promotionsnotification\Model\ResourceModel\Page');
    }
}
