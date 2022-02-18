<?php
namespace Magebees\Promotionsnotification\Model\ResourceModel;

/**
 * Review resource model
 */
class Store extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table. Define other tables name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magebees_notification_store', 'notification_store_id');
    }
}
