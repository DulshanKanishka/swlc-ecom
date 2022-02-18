<?php
namespace Magenest\Xero\Model\ResourceModel;

/**
 * Class Queue
 * @package Magenest\Xero\Model\ResourceModel
 */
class XmlLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('magenest_xero_xml_log', 'id');
    }
}
