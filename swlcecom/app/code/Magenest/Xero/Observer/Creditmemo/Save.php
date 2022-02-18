<?php
namespace Magenest\Xero\Observer\Creditmemo;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Observer\Synchronize;
use Magenest\Xero\Model\QueueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Store\Model\StoreManager;
use Magenest\Xero\Model\Config;

class Save extends Synchronize
{
    protected $pathEnable = 'magenest_xero_config/xero_credit/enabled';
    protected $pathSyncOption = 'magenest_xero_config/xero_credit/sync_mode';
    protected $pathTimeOption = 'magenest_xero_config/xero_credit/cron_time';

    /**
     * @var Synchronization\CreditNote
     */
    protected $syncModel;

    /**
     * @var Synchronization\Allocation
     */
    protected $syncAllocation;

    protected $xeroHelper;

    public function __construct(
        QueueFactory $queueFactory,
        ScopeConfigInterface $config,
        StoreManager $storeManager,
        Synchronization\CreditNote $syncModel,
        Synchronization\Allocation $syncAllocation,
        Helper $helper
    ) {
        $this->syncAllocation = $syncAllocation;
        $this->syncModel = $syncModel;
        $this->xeroHelper = $helper;
        parent::__construct($queueFactory, $config, $storeManager);
    }

    public function execute(EventObserver $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
            $creditmemo = $observer->getCreditmemo();
            if ($this->enable) {
                if ($this->syncMode == 1) {
                    /** cron job mode */
                    $queue = $this->queueFactory->create()
                        ->getCollection()
                        ->addFieldToFilter('type', 'CreditNote')
                        ->addFieldToFilter('entity_id', $creditmemo->getIncrementId())
                        ->getFirstItem();
                    if ($queue->getId()) {
                        /** Creditmemo existed in queue */
                        $queue = $this->queueFactory->create()->load($queue->getId());
                        $queue->setEnqueueTime(time());
                        $queue->save();
                        return $this;
                    }
                    $queue = $this->queueFactory->create();
                    $data = [
                        'type' => 'CreditNote',
                        'entity_id' => $creditmemo->getIncrementId(),
                        'enqueue_time' => time(),
                        'priority' => 1,
                    ];
                    $queue->setData($data);
                    $queue->save();
                } else {
                    /** immediately mode */
                    if ($this->xeroHelper->isMultipleWebsiteEnable()) {
                        $this->xeroHelper->setScope('websites');
                        $this->xeroHelper->setScopeId($creditmemo->getStore()->getWebsiteId());
                    }
                    if (!$this->xeroHelper->getConfig(Config::XML_PATH_XERO_IS_CONNECTED)) {
                        return $this;
                    }
                    $xml = $this->syncModel->addRecord($creditmemo);
                    if ($xml == '') {
                        return $this;
                    }
                    $xml = '<Creditmemos>' . $xml . '</Creditmemos>';
                    $this->syncModel->syncAllGuestToXero();
                    $this->syncModel->syncData($xml);
                    $this->syncModel->syncRefundXml($creditmemo);
                }
                return $this;
            }
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('Xero Save Creditmemo Exception: '.$e->getMessage());
        } finally {
            return $this;
        }
    }
}
