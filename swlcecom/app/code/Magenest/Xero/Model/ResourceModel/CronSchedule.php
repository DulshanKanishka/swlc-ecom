<?php
namespace Magenest\Xero\Model\ResourceModel;

/**
 * Class CronSchedule
 * @package Magenest\Xero\Model\ResourceModel
 */
class CronSchedule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('cron_schedule', 'schedule_id');
    }
}
