<?php
namespace Magenest\Xero\Model\ResourceModel\Queue;

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
        $this->_init('Magenest\Xero\Model\Queue', 'Magenest\Xero\Model\ResourceModel\Queue');
    }

    public function getAllIds()
    {
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset(\Magento\Framework\DB\Select::ORDER);
//        $idsSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
//        $idsSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $idsSelect->reset(\Magento\Framework\DB\Select::COLUMNS);

        $idsSelect->columns($this->getResource()->getIdFieldName(), 'main_table');
        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }
}
