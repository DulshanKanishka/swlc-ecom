<?php
namespace Magenest\Xero\Model;

class CronSchedule extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('Magenest\Xero\Model\ResourceModel\CronSchedule');
    }
}
