<?php
namespace Magenest\Xero\Observer\Order;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Observer\Synchronize;
use Magenest\Xero\Model\QueueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManager;
use Magenest\Xero\Model\Config;

class Save extends Synchronize
{
    protected $type = 'OrderToInvoice';
    /**
     * @var Synchronization\Order
     */
    protected $syncModel;

    protected $xeroHelper;

    public function __construct(
        QueueFactory $queueFactory,
        ScopeConfigInterface $config,
        StoreManager $storeManager,
        Synchronization\Order $syncModel,
        Helper $helper
    ) {
        $this->syncModel = $syncModel;
        $this->xeroHelper = $helper;
        parent::__construct($queueFactory, $config, $storeManager);
    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        try {
            /** @var OrderInterface $order */
            $order = $observer->getOrder();
            if ($this->enable && $this->orderToInvoice) {
                if ($this->syncMode == 1) {
                    $incrementId = $order->getIncrementId();
                    /** @var \Magenest\Xero\Model\Queue $queue */
                    $queue = $this->queueFactory->create()
                        ->getCollection()
                        ->addFieldToFilter('type', $this->type)
                        ->addFieldToFilter('entity_id', $incrementId)
                        ->getFirstItem();

                    if ($queue->getId()) {
                        /** order existed in queue */
                        $queue = $this->queueFactory->create()->load($queue->getId());
                        $queue->setEnqueueTime(time());
                        $queue->save();
                        return $this;
                    }

                    $queue = $this->queueFactory->create();
                    $data = [
                        'type' => $this->type,
                        'entity_id' => $incrementId,
                        'enqueue_time' => time(),
                        'priority' => 1,
                    ];

                    $queue->setData($data);
                    $queue->save();
                } else {
                    if ($this->xeroHelper->isMultipleWebsiteEnable()) {
                        $this->xeroHelper->setScope('websites');
                        $this->xeroHelper->setScopeId($order->getStore()->getWebsiteId());
                    }
                    if (!$this->xeroHelper->getConfig(Config::XML_PATH_XERO_IS_CONNECTED)) {
                        return $this;
                    }
                    /** immediately mode */
                    $xml = $this->syncModel->addRecord($order);
                    if ($xml !== '') {
                        $xml = '<Invoices>' . $xml . '</Invoices>';
                    }
                    $this->syncModel->syncAllGuestToXero();
                    $this->syncModel->syncData($xml);
                    $this->syncModel->syncPayments();
                }
            }
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('Xero Save Order Exception: '.$e->getMessage());
        } finally {
            return $this;
        }
    }
}
