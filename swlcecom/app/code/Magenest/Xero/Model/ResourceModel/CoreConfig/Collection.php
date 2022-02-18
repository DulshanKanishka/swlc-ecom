<?php
namespace Magenest\Xero\Model\ResourceModel\CoreConfig;

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
        $this->_init('Magenest\Xero\Model\CoreConfig', 'Magenest\Xero\Model\ResourceModel\CoreConfig');
    }

}
