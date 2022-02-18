<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\Chart;

use Magenest\AbandonedCart\Model\LogContent;
use Magento\Quote\Model\Quote;
use Magento\Framework\DB\Select;
use Magento\Sales\Model\Order;

class AbandonedCart extends \Magenest\AbandonedCart\Block\Adminhtml\Chart\AbstractChart
{
    protected $abandonedCarts;

    protected $customerAbandonedCarts;

    /** @var \Magenest\AbandonedCart\Model\AbandonedCartFactory $abandonedCartFactory */
    protected $abandonedCartFactory;

    protected $guestAbandonedCarts;

    protected $nonAbandonedCarts;

    protected $carts;

    /** @var \Magento\Quote\Model\QuoteFactory $quoteFactory */
    protected $quoteFactory;

    protected $repurchasedAbandonedCarts;

    protected $nonRepurchasedAbandonedCarts;

    /** @var \Magenest\AbandonedCart\Model\LogContentFactory $_logContentFactory */
    protected $_logContentFactory;

    /** @var \Magento\Sales\Model\OrderFactory $_orderFactory */
    protected $_orderFactory;

    /**
     * AbandonedCart constructor.
     *
     * @param \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magenest\AbandonedCart\Model\AbandonedCartFactory $abandonedCartFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magenest\AbandonedCart\Model\AbandonedCartFactory $abandonedCartFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->_logContentFactory   = $logContentFactory;
        $this->_orderFactory        = $orderFactory;
        $this->quoteFactory         = $quoteFactory;
        $this->abandonedCartFactory = $abandonedCartFactory;
        parent::__construct($context, $data);
    }

    public function getAbandonedCarts()
    {
        if ($this->abandonedCarts) {
            return $this->abandonedCarts;
        }
        $abadonedCarts        = $this->getCustomerAbandonedCarts() + $this->getGuestAbandonedCarts();
        $this->abandonedCarts = $abadonedCarts;
        return $this->abandonedCarts;
    }

    public function getCustomerAbandonedCarts()
    {
        if ($this->customerAbandonedCarts) {
            return $this->customerAbandonedCarts;
        }
        $cartModel                    = $this->abandonedCartFactory->create();
        $from                         = $this->getFromDate();
        $to                           = $this->getToDate();
        $collection                   = $cartModel->getCollection()
            ->addFieldToFilter('type', 'customer')
            ->getSelect()
            ->reset(Select::COLUMNS)
            ->where("created_at >= '" . $from . "' AND created_at <= '" . $to . "' AND status = '0'")
            ->columns([
                'id'
            ]);
        $rows                         = $cartModel->getResource()->getConnection()->fetchAll($collection);
        $this->customerAbandonedCarts = count($rows);
        return $this->customerAbandonedCarts;
    }

    public function getGuestAbandonedCarts()
    {
        if ($this->guestAbandonedCarts) {
            return $this->guestAbandonedCarts;
        }
        $cartModel                 = $this->abandonedCartFactory->create();
        $from                      = $this->getFromDate();
        $to                        = $this->getToDate();
        $collection                = $cartModel->getCollection()
            ->addFieldToFilter('type', 'guest')
            ->getSelect()
            ->reset(Select::COLUMNS)
            ->where("created_at >= '" . $from . "' AND created_at <= '" . $to . "' AND status = '0'")
            ->columns([
                'id'
            ]);
        $rows                      = $cartModel->getResource()->getConnection()->fetchAll($collection);
        $this->guestAbandonedCarts = count($rows);
        return $this->guestAbandonedCarts;
    }

    public function getAbandonedCartData()
    {
        return [
            'Abandoned' => $this->getAbandonedCarts(),
            'Completed' => $this->getNonAbadonedCarts()
        ];
    }

    public function getNonAbadonedCarts()
    {
        if ($this->nonAbandonedCarts) {
            return $this->nonAbandonedCarts;
        }
        $this->nonAbandonedCarts = $this->getCarts() - $this->getAbandonedCarts();
        return $this->nonAbandonedCarts;
    }

    public function getCarts()
    {
        if ($this->carts) {
            return $this->carts;
        }
        $from        = $this->getFromDate();
        $to          = $this->getToDate();
        $quoteModel  = $this->quoteFactory->create();
        $carts       = $quoteModel->getCollection()
            ->getSelect()
            ->where("created_at >= '" . $from . "' AND created_at <= '" . $to . "'");
        $rows        = $quoteModel->getResource()->getConnection()->fetchAll($carts);
        $this->carts = count($rows);
        return $this->carts;
    }

    public function getGuestAbandonedCartData()
    {
        return [
            'Guest'    => $this->getGuestAbandonedCarts(),
            'Customer' => $this->getCustomerAbandonedCarts()
        ];
    }

    public function getRepurchasedCartData()
    {
        return [
            'Repurchased' => $this->getRepurchasedAbandonedCarts(),
            'Abandoned'   => $this->getNonRepurchasedAbandonedCarts()
        ];
    }

    public function getRepurchasedAbandonedCarts()
    {
        $repurchasedAbandonedCarts = $this->abandonedCartFactory->create();
        $from                      = $this->getFromDate();
        $to                        = $this->getToDate();
        $collections               = $repurchasedAbandonedCarts->getCollection()
            ->getSelect()
            ->where("created_at >= '" . $from . "' AND created_at <= '" . $to . "' AND placed > 0 AND status = '0'");
        $rows                      = $repurchasedAbandonedCarts->getResource()->getConnection()->fetchAll($collections);
        return count($rows);
    }

    public function getNonRepurchasedAbandonedCarts()
    {
        $repurchasedAbandonedCarts = $this->abandonedCartFactory->create();
        $from                      = $this->getFromDate();
        $to                        = $this->getToDate();
        $collections               = $repurchasedAbandonedCarts
            ->getCollection()
            ->getSelect()
            ->where("created_at >= '" . $from . "' AND created_at <= '" . $to . "' AND placed is null")->__toString();
        $rows                      = $repurchasedAbandonedCarts->getResource()->getConnection()->fetchAll($collections);
        return count($rows);
    }

    public function getAbandonedCartLineChart()
    {
        $abandonedCart = $this->abandonedCartFactory->create();
        $from          = $this->getFromDate();
        $to            = $this->getToDate();
        $select        = $abandonedCart->getCollection()->getSelect()->reset(Select::COLUMNS)
            ->group(
                'CAST(main_table.created_at AS DATE)'
            )->order(
                'CAST(main_table.created_at AS DATE) ASC'
            )->where("created_at >= '" . $from . "' AND created_at <= '" . $to . "' AND status = '0'")
            ->columns([
                'COUNT(main_table.id) as count',
                'created_at' => new \Zend_Db_Expr('CAST(main_table.created_at AS DATE)')
            ]);
        $rows          = $abandonedCart->getResource()->getConnection()->fetchAll($select);
        return $rows;
    }

    public function getTotalRestore()
    {
        $logContent           = $this->_logContentFactory->create();
        $from                 = $this->getFromDate();
        $to                   = $this->getToDate();
        $logContentCollection = $logContent->getCollection()->getSelect()->where(
            "created_at >= '" . $from . "' AND created_at <= '" . $to . "' AND main_table.is_restore > 0"
        );
        $rows                 = $logContent->getResource()->getConnection()->fetchAll($logContentCollection);
        $result               = count($rows);
        return $result;
    }

    public function getTotalOrder()
    {
        $abandonedCart           = $this->abandonedCartFactory->create();
        $from                    = $this->getFromDate();
        $to                      = $this->getToDate();
        $abandonedCartCollection = $abandonedCart->getCollection()
            ->getSelect()
            ->where("created_at >= '" . $from . "' AND created_at <= '" . $to . "' AND placed > 0");
        $rows                    = $abandonedCart->getResource()->getConnection()->fetchAll($abandonedCartCollection);
        $result                  = count($rows);
        return $result;
    }

    public function getGrandTotal()
    {
        $order              = $this->_orderFactory->create();
        $collection         = $order->getCollection();
        $abandonedCartTable = $collection->getTable('magenest_abacar_list');
        $from               = $this->getFromDate();
        $to                 = $this->getToDate();
        $select             = $collection->getSelect()->reset(Select::COLUMNS)
            ->joinLeft(
                ['a' => $abandonedCartTable],
                'main_table.entity_id = a.placed',
                []
            )->where(
                "main_table.`created_at` >= '" . $from . "' AND main_table.`created_at` <= '" . $to . "' AND a.placed > 0"
            )->columns([
                'SUM(main_table.base_grand_total) as totals',
                'created_at' => new \Zend_Db_Expr('CAST(main_table.created_at AS DATE)'),
                'base_currency_code'
            ])->__toString();
        $rows               = $order->getResource()->getConnection()->fetchAll($select);
        return $rows[0]['totals'] . ' ' . $rows[0]['base_currency_code'];
    }
}