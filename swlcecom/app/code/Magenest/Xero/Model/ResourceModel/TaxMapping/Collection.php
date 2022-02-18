<?php
namespace Magenest\Xero\Model\ResourceModel\TaxMapping;

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
        $this->_init('Magenest\Xero\Model\TaxMapping', 'Magenest\Xero\Model\ResourceModel\TaxMapping');
    }
}
