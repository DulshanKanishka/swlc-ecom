<?php
namespace Magenest\Xero\Observer;

use Magenest\Xero\Model\QueueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;

abstract class Synchronize implements ObserverInterface
{
    protected $pathEnable = 'magenest_xero_config/xero_order/enabled';
    protected $pathSyncOption = 'magenest_xero_config/xero_order/sync_mode';
    protected $pathTimeOption = 'magenest_xero_config/xero_order/cron_time';

    protected $pathOrderToInvocie = 'magenest_xero_config/xero_order/order_invoice_enabled';

    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * @var mixed
     */
    protected $syncMode;

    protected $enable;

    protected $syncTime;

    protected $storeManager;

    protected $config;

    protected $orderToInvoice;

    public function __construct(
        QueueFactory $queueFactory,
        ScopeConfigInterface $config,
        StoreManager $storeManager
    ) {
        $this->queueFactory = $queueFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->getSyncSettings();
    }

    protected function getSyncSettings()
    {
        $this->enable = $this->config->getValue($this->pathEnable);
        $this->syncMode = $this->config->getValue($this->pathSyncOption);
        $this->syncTime = $this->config->getValue($this->pathTimeOption);
        $this->orderToInvoice = $this->config->getValue($this->pathOrderToInvocie);
    }
}
