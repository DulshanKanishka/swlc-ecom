<?php
namespace Magenest\Xero\Model\ResourceModel;

/**
 * Class Log
 * @package Magenest\Xero\Model\ResourceModel
 */
class Log extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('magenest_xero_log', 'id');
    }
}
