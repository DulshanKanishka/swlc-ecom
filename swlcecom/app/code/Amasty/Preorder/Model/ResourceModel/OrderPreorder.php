<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Preorder
 */

/**
 * Copyright © 2016 Amasty. All rights reserved.
 */

namespace Amasty\Preorder\Model\ResourceModel;

class OrderPreorder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('amasty_preorder_order_preorder', 'id');
    }

    public function getWarningByOrderId($orderId)
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $select = $connection->select()->from($table)->where('order_id = ?', $orderId);

        $result = $connection->fetchRow($select);
        return $result['warning'];
    }

    public function getIsOrderProcessed($orderId)
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $select = $connection->select()
            ->from($table)
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns('id')
            ->where('order_id = ?', $orderId);
        $record = $connection->fetchRow($select);

        return !!$record;
    }

    /**
     * Is order have Preorder Flag
     *
     * @since 1.0.7 bugfix - added Reset columns from select
     * @param int $orderId
     *
     * @return bool
     */
    public function getOrderIsPreorderFlag($orderId)
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $select = $connection->select()
            ->from($table)
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns('is_preorder')
            ->where('order_id = ?', $orderId);

        return (bool) $connection->fetchOne($select);
    }
}
