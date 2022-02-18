<?php
namespace Magebees\Promotionsnotification\Model\ResourceModel;

/**
 * Review resource model
 */
class Product extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table. Define other tables name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magebees_notification_product', 'notification_product_id');
    }
}
