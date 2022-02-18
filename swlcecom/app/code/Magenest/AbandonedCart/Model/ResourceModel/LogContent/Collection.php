<?php

namespace Magenest\AbandonedCart\Model\ResourceModel\LogContent;

use Magenest\AbandonedCart\Model\Config\Source\Mail as EmailStatus;
use Magento\Framework\DB\Select;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'id';

    public function _construct()
    {
        $this->_init('Magenest\AbandonedCart\Model\LogContent', 'Magenest\AbandonedCart\Model\ResourceModel\LogContent');
    }

    public function getMailsNeedToBeSent()
    {
        $current_time = new \DateTime();
        $currentTime  = $current_time->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        $cond         = 'send_date < ' . "'$currentTime'";

        $this->getSelect()->where($cond);
        $this->addFieldToFilter('status', EmailStatus::STATUS_QUEUED);
        $this->addFieldToFilter('type', 'Email');
        $this->setOrder('created_at', self::SORT_ORDER_ASC);
        return $this;
    }

    public function getSMSNeedToBeSent()
    {
        $current_time = new \DateTime();
        $currentTime  = $current_time->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        $cond         = 'send_date < ' . "'$currentTime'";

        $this->getSelect()->where($cond);
        $this->addFieldToFilter('status', EmailStatus::STATUS_QUEUED);
        $this->addFieldToFilter('type', 'SMS');
        $this->setOrder('created_at', self::SORT_ORDER_ASC);
        return $this;
    }
}