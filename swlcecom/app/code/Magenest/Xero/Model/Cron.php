<?php
namespace Magenest\Xero\Model;

use Magenest\Xero\Model\Synchronization;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class Cron
 * @package Magenest\Xero\Model
 */
class Cron
{
    /**
     * @var Synchronization\Customer
     */
    protected $_customer;

    /**
     * @var Synchronization\Item
     */
    protected $_item;

    /**
     * @var Synchronization\Invoice
     */
    protected $_invoice;

    /**
     * @var Synchronization\Order
     */
    protected $_order;

    /**
     * @var Synchronization\CreditNote
     */
    protected $_creditNote;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DateTime
     */
    protected $dateModel;

    protected $currentMin;
    protected $currentHour;

    /**
     * Cron constructor.
     *
     * @param Synchronization\Customer $customer
     * @param Synchronization\Item $item
     * @param Synchronization\Invoice $invoice
     * @param Synchronization\Order $order
     * @param Synchronization\CreditNote $creditNote
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime $dateModel
     */
    public function __construct(
        Synchronization\Customer $customer,
        Synchronization\Item $item,
        Synchronization\Invoice $invoice,
        Synchronization\Order $order,
        Synchronization\CreditNote $creditNote,
        ScopeConfigInterface $scopeConfig,
        DateTime $dateModel
    ) {
        $this->_creditNote = $creditNote;
        $this->_customer = $customer;
        $this->_item = $item;
        $this->_invoice = $invoice;
        $this->_order = $order;
        $this->scopeConfig = $scopeConfig;
        $this->dateModel = $dateModel;
        $this->currentMin = date('i');
        $this->currentHour = date('h');
    }

    /**
     * Get Config Value
     *
     * @param $type
     * @return mixed
     */
    protected function getConfigValue($type)
    {
        $path = 'magenest_xero_config/xero_'. $type .'/cron_time';

        return $this->scopeConfig->getValue($path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * sync all queued data to xero
     * maximum 250 items at a time
     */
    public function syncData()
    {
        if ($time = $this->getConfigValue('contact')) {
            if ($this->calculateTime($time)) {
                $this->_customer->syncCronJobMode(true);
            }
        }

        if ($time = $this->getConfigValue('item')) {
            if ($this->calculateTime($time)) {
                $this->_item->syncCronJobMode(true);
            }
        }

        if ($time = $this->getConfigValue('order')) {
            if ($this->calculateTime($time)) {
                $path = 'magenest_xero_config/xero_order/order_invoice_enabled';
                if ($this->scopeConfig->getValue($path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT))
                    $this->_order->syncCronJobMode(true);
                else
                    $this->_invoice->syncCronJobMode(true);
            }
        }

        if ($time = $this->getConfigValue('credit')) {
            if ($this->calculateTime($time)) {
                $this->_creditNote->syncCronJobMode(true);
            }
        }
    }

    /**
     * Calculate time
     *
     * @param $time
     * @return bool
     */
    protected function calculateTime($time)
    {
        $cronHours = floor($time/60);
        $minute = $this->currentMin;
        $hour = $this->currentHour;

        if ($cronHours > 0) {
            return ($minute == 0 && $hour % $cronHours == 0);
        } else {
            if ($minute == 0) {
                $minute = 60;
            }
            return ($minute % $time == 0);
        }
    }
}
