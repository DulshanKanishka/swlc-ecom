<?php
namespace Magenest\Xero\Model\ResourceModel;

/**
 * Class Log
 * @package Magenest\Xero\Model\ResourceModel
 */
class CoreConfig extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('core_config_data', 'config_id');
    }
}
