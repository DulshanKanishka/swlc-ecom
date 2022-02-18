<?php
namespace Magenest\Xero\Observer\Setting\Adminhtml;

use Magenest\Xero\Observer\Synchronize;
use Magenest\Xero\Model\QueueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Store\Model\StoreManager;
use Magenest\Xero\Model\Cache;

class Save extends Synchronize
{
    protected $pathEnable = 'magenest_xero_config/xero_order/enabled';
    protected $pathSyncOption = 'magenest_xero_config/xero_order/order_invoice_enabled';
    protected $pathTimeOption = 'magenest_xero_config/xero_order/cron_time';

    protected $orderEnablePath = 'magenest_xero_config/xero_order/order_enabled';
    protected $invoiceEnablePath = 'magenest_xero_config/xero_order/invoice_enabled';

    protected $orderFactory;
    protected $configWriter;

    /**
     * Save constructor.
     * @param QueueFactory $queueFactory
     * @param ScopeConfigInterface $config
     * @param StoreManager $storeManager
     * @param OrderFactory $orderFactory
     * @param WriterInterface $writer
     */
    public function __construct(
        QueueFactory $queueFactory,
        ScopeConfigInterface $config,
        StoreManager $storeManager,
        OrderFactory $orderFactory,
        WriterInterface $writer
    ) {
        $this->orderFactory = $orderFactory;
        $this->configWriter = $writer;
        parent::__construct($queueFactory, $config, $storeManager);
    }

    public function execute(EventObserver $observer)
    {
        $this->clearQueue($observer);
        $this->checkDisable();
    }

    protected function clearQueue(EventObserver $observer)
    {
        try {
            $connection = $this->orderFactory->create()->getResource()->getConnection();
            $queueModel = $this->queueFactory->create();
            $queueTable = $queueModel->getResource()->getMainTable();

            $type = "%Invoice%";
            if ($this->enable) {
                $type = "OrderToInvoice";
                if ($this->syncMode == "1") {
                    $type = "InvoiceToInvoice";
                }
            }
            $connection->delete($queueTable, "type like '".$type."'");

        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('Xero Save Product Exception: '.$e->getMessage());
        } finally {
            return $this;
        }
    }

    protected function checkDisable()
    {
        $enable = $this->config->getValue($this->pathEnable);
        $isOrder = $this->config->getValue($this->pathSyncOption);

        if ($enable && $isOrder) {
            $this->configWriter->save($this->orderEnablePath, 1);
            $this->configWriter->save($this->invoiceEnablePath, 0);
        } elseif ($enable && $isOrder == 0) {
            $this->configWriter->save($this->orderEnablePath, 0);
            $this->configWriter->save($this->invoiceEnablePath, 1);
        } else {
            $this->configWriter->save($this->orderEnablePath, 0);
            $this->configWriter->save($this->invoiceEnablePath, 0);
        }
        Cache::refreshCache();
    }
}
