<?php
namespace Magenest\Xero\Model\ResourceModel\Log;

/**
 * Class Collection
 * @package Magenest\Xero\Model\ResourceModel\Log
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
        $this->_init('Magenest\Xero\Model\Log', 'Magenest\Xero\Model\ResourceModel\Log');
    }

    protected function _initSelect()
    {
        parent::_initSelect(); // TODO: Change the autogenerated stub

        $joinTable = $this->getTable('magenest_xero_xml_log');
        return $this->getSelect()->joinLeft(
            ['xml_log' => $joinTable],
            "main_table.xml_log_id = xml_log.id",
            ['scope', 'scope_id', 'xml_log_table_id' => 'xml_log.id']
        );
    }
}