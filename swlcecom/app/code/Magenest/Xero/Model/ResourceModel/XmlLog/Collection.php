<?php
namespace Magenest\Xero\Model\ResourceModel\XmlLog;

/**
 * Class Collection
 * @package Magenest\Xero\Model\ResourceModel\Queue
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magenest\Xero\Model\XmlLog', 'Magenest\Xero\Model\ResourceModel\XmlLog');
    }
}
