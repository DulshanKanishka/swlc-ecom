<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\Chart;

use Magento\Framework\Stdlib\DateTime;

class AbstractChart extends \Magento\Backend\Block\Widget
{
    public function getPeriodFromParam()
    {
        return $this->getRequest()->getParam('from');
    }

    public function getPeriodToParam()
    {
        return $this->getRequest()->getParam('to');
    }

    public function getFromDate()
    {
        $from     = $this->getRequest()->getParam('from');
        $fromDate = $from != "" ? $from : "01-01-2000";
        return $this->getPhpFormatDate($fromDate);
    }

    public function getToDate()
    {
        $to     = $this->getRequest()->getParam('to');
        $toDate = $to != "" ? $to . " 23:59:59" : "31-12-2100";
        return $this->getPhpFormatDate($toDate);
    }

    protected function applyPeriodToCollection($collection, $dateFields)
    {
        if (!is_array($dateFields)) {
            $dateFields = [$dateFields];
        }
        if ($from = $this->getPeriodFromParam()) {
            $fromFields = [];
            foreach ($dateFields as $field) {
                $fromFields[] = $field . " >= '" . $this->getPhpFormatDate($from) . "'";
            }
            $collection->getSelect()
                ->where(implode(' OR ', $fromFields));
        }
        if ($to = $this->getPeriodToParam()) {
            $toFields = [];
            foreach ($dateFields as $field) {
                $toFields[] = $field . " <= '" . $this->getPhpFormatDate($to) . "'";
            }
            $collection->getSelect()
                ->where(implode(' OR ', $toFields));
        }
        return $collection;
    }

    public function getPhpFormatDate($date)
    {
        return date(DateTime::DATETIME_PHP_FORMAT, strtotime($date));
    }
}