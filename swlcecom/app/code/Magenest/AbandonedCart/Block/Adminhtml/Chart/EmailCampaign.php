<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\Chart;

use Magenest\AbandonedCart\Model\LogContent;
use Magenest\AbandonedCart\Model\Unsubscribe;
use Magento\Framework\DB\Select;
use Magenest\AbandonedCart\Model\Config\Source\UnsubscriberStatus;

class EmailCampaign extends \Magenest\AbandonedCart\Block\Adminhtml\Chart\AbstractChart
{
    protected $emailTotalCounts;

    protected $openedEmailTotalCounts;

    protected $clickedEmailTotalCounts;

    /** @var \Magenest\AbandonedCart\Model\LogContentFactory $_logContentFactory */
    protected $_logContentFactory;

    /** @var \Magenest\AbandonedCart\Model\UnsubscribeFactory $_unsubscribeFactory */
    protected $_unsubscribeFactory;

    protected $unsubscribeCounts;

    /**
     * EmailCampaign constructor.
     *
     * @param \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory
     * @param \Magenest\AbandonedCart\Model\UnsubscribeFactory $unsubscribeFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory,
        \Magenest\AbandonedCart\Model\UnsubscribeFactory $unsubscribeFactory,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->_logContentFactory  = $logContentFactory;
        $this->_unsubscribeFactory = $unsubscribeFactory;
        parent::__construct($context, $data);
    }

    public function getEmailCount()
    {
        $logContent     = $this->_logContentFactory->create();
        $from           = $this->getFromDate();
        $to             = $this->getToDate();
        $mailCollection = $logContent->getCollection()
            ->addFieldToFilter('type', 'Email')
            ->getSelect()
            ->where("main_table.send_date >= '" . $from . "' AND main_table.send_date <= '" . $to . "'");
        $rows           = $logContent->getResource()->getConnection()->fetchAll($mailCollection);
        return count($rows);
    }

    public function getOpeningRatesData()
    {
        return [
            'Opened'   => $this->getOpenedEmailCount(),
            'Unopened' => $this->getUnopenedEmailCount()
        ];
    }

    public function getOpenedEmailCount()
    {
        $from           = $this->getFromDate();
        $to             = $this->getToDate();
        $mailCollection = $this->_logContentFactory->create();
        $select         = $mailCollection->getCollection()->getSelect()
            ->where("main_table.send_date >= '" . $from . "' AND main_table.send_date <= '" . $to . "' AND opened is not null AND opened > 0");
        $rows           = $mailCollection->getResource()->getConnection()->fetchAll($select);
        return count($rows);
    }

    public function getUnopenedEmailCount()
    {
        return $this->getEmailCount() - $this->getOpenedEmailCount();
    }

    public function getClickingRatesData()
    {
        return [
            'Clicked'   => $this->getClickedEmailCount(),
            'Unclicked' => $this->getUnclickedEmailCount()
        ];
    }

    public function getUnsubscribeRatesData()
    {
        return [
            'Subscribe'   => $this->getSubscribeEmailCount(),
            'Unsubscribe' => $this->getUnsubscribeEmailCount()
        ];
    }

    public function getClickedEmailCount()
    {
        $from           = $this->getFromDate();
        $to             = $this->getToDate();
        $mailCollection = $this->_logContentFactory->create();
        $select         = $mailCollection->getCollection()
            ->addFieldToFilter('type', 'Email')
            ->getSelect()
            ->where("main_table.send_date >= '" . $from . "' AND main_table.send_date <= '" . $to . "' AND clicks is not null AND clicks > 0");
        $rows           = $mailCollection->getResource()->getConnection()->fetchAll($select);

        return count($rows);
    }

    public function getUnsubscribeEmailCount()
    {
        $from                  = $this->getFromDate();
        $to                    = $this->getToDate();
        $unsubscribeCollection = $this->_unsubscribeFactory->create();
        $select                = $unsubscribeCollection->getCollection()
            ->getSelect()
            ->where("main_table.created_at >= '" . $from . "' AND main_table.created_at <= '" . $to . "' AND unsubscriber_status = '" . UnsubscriberStatus::UNSUBSCRIBED . "'");
        $rows                  = $unsubscribeCollection->getResource()->getConnection()->fetchAll($select);

        return count($rows);
    }

    public function getSubscribeEmailCount()
    {
        return $this->countEmail() - $this->getUnsubscribeEmailCount();
    }

    public function countEmail()
    {
        $from   = $this->getFromDate();
        $to     = $this->getToDate();
        $mail   = $this->_logContentFactory->create();
        $select = $mail->getCollection()
            ->getSelect()->reset(Select::COLUMNS)
            ->group('recipient_adress')
            ->columns([
                'recipient_adress as email'
            ])
            ->where(
                "main_table.send_date >= '" . $from . "' AND main_table.send_date <= '" . $to . "' AND type='Email'"
            );
        $rows   = $mail->getResource()->getConnection()->fetchAll($select);
        return count($rows);
    }

    public function getUnclickedEmailCount()
    {
        return $this->getEmailCount() - $this->getClickedEmailCount();
    }

    public function getEmailsLineData()
    {
        $mail   = $this->_logContentFactory->create();
        $from   = $this->getFromDate();
        $to     = $this->getToDate();
        $select = $mail->getCollection()->addFieldToFilter('type', 'Email')->getSelect()->reset(Select::COLUMNS)
            ->group(
                'send_at'
            )->order(
                'send_at ASC'
            )->where("main_table.send_date >= '" . $from . "' AND main_table.send_date <= '" . $to . "'")
            ->columns([
                'COUNT(main_table.id) as count',
                'SUM(IF(opened>0,1,0)) as opened_count',
                'SUM(IF(clicks>0,1,0)) as click_count',
                'send_at' => new \Zend_Db_Expr('CAST(main_table.send_date AS DATE)')
            ]);
        $rows   = $mail->getResource()->getConnection()->fetchAll($select);
        return $rows;
    }

}