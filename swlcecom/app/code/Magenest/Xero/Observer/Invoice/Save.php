<?php
namespace Magenest\Xero\Observer\Invoice;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Observer\Synchronize;
use Magenest\Xero\Model\QueueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Store\Model\StoreManager;
use Magenest\Xero\Model\Config;


class Save extends Synchronize
{
    /**
     * @var Synchronization\Invoice
     */
    protected $syncModel;

    protected $syncPayment;

    protected $xeroHelper;

    public function __construct(
        QueueFactory $queueFactory,
        ScopeConfigInterface $config,
        StoreManager $storeManager,
        Synchronization\Invoice $syncModel,
        Synchronization\Payment $payment,
        Helper $helper
    ) {
        $this->syncModel = $syncModel;
        $this->syncPayment = $payment;
        $this->xeroHelper = $helper;
        parent::__construct($queueFactory, $config, $storeManager);
    }

    public function execute(EventObserver $observer)
    {
        try {
            /** @var InvoiceInterface $invoice */
            $invoice = $observer->getInvoice();
            $this->getSyncSettings($invoice->getStore());
            if ($this->enable && !$this->orderToInvoice) {
                if ($this->syncMode == 1) {
                    /** cron job mode */
                    $queue = $this->queueFactory->create()
                        ->getCollection()
                        ->addFieldToFilter('type', 'InvoiceToInvoice')
                        ->addFieldToFilter('entity_id', $invoice->getIncrementId())
                        ->getFirstItem();
                    if ($queue->getId()) {
                        /** invoice existed in queue */
                        $queue = $this->queueFactory->create()->load($queue->getId());
                        $queue->setEnqueueTime(time());
                        $queue->save();
                        return $this;
                    }
                    $queue = $this->queueFactory->create();
                    $data = [
                        'type' => 'InvoiceToInvoice',
                        'entity_id' => $invoice->getIncrementId(),
                        'enqueue_time' => time(),
                        'priority' => 1,
                    ];
                    $queue->setData($data);
                    $queue->save();
                } else {
                    if ($this->xeroHelper->isMultipleWebsiteEnable()){
                        $this->xeroHelper->setScope('websites');
                        $this->xeroHelper->setScopeId($invoice->getStore()->getWebsiteId());
                    }
                    if (!$this->xeroHelper->getConfig(Config::XML_PATH_XERO_IS_CONNECTED)) {
                        return $this;
                    }
                    /** immediately mode */
                    $xml = $this->syncModel->addRecord($invoice);
                    if ($xml !== '') {
                        $xml = '<Invoices>' . $xml . '</Invoices>';
                    }
                    $this->syncModel->syncAllGuestToXero();
                    $this->syncModel->syncData($xml);
                    $this->syncPayment->syncPayments();
                }
                return $this;
            }
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('Xero Save Invoice Exception: '.$e->getMessage());
        } finally {
            return $this;
        }
    }
}
