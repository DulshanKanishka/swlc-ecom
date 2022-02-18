<?php
namespace Magenest\Xero\Model\ResourceModel;

/**
 * Class RequestLog
 * @package Magenest\Xero\Model\ResourceModel
 */
class RequestLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('magenest_xero_request', 'id');
    }
}
