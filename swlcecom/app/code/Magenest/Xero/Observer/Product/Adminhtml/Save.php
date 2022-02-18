<?php
namespace Magenest\Xero\Observer\Product\Adminhtml;

use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Observer\Synchronize;
use Magenest\Xero\Model\QueueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Store\Model\StoreManager;
use Magenest\Xero\Model\Helper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class Save extends Synchronize
{
    protected $pathEnable = 'magenest_xero_config/xero_item/enabled';
    protected $pathSyncOption = 'magenest_xero_config/xero_item/sync_mode';
    protected $pathTimeOption = 'magenest_xero_config/xero_item/cron_time';

    /**
     * @var Synchronization\Item
     */
    protected $syncModel;

    /**
     * @var Synchronization\BankTransaction
     */
    protected $syncTransaction;

    protected $xeroHelper;

    protected $collectionFactory;
    /**
     * Save constructor.
     * @param QueueFactory $queueFactory
     * @param ScopeConfigInterface $config
     * @param StoreManager $storeManager
     * @param Synchronization\Item $syncModel
     * @param Synchronization\BankTransaction $syncTransaction
     * @param Helper $helper
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        QueueFactory $queueFactory,
        ScopeConfigInterface $config,
        StoreManager $storeManager,
        Synchronization\Item $syncModel,
        Synchronization\BankTransaction $syncTransaction,
        Helper $helper,
        CollectionFactory $collectionFactory
    ) {
        $this->syncModel = $syncModel;
        $this->syncTransaction = $syncTransaction;
        $this->xeroHelper = $helper;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($queueFactory, $config, $storeManager);
    }

    public function execute(EventObserver $observer)
    {
        try {
            /** @var ProductInterface $product */
            $product = $observer->getProduct();

            if ($this->enable) {
                if ($this->syncMode == 1) {
                    /** cron job mode */
                    $queue = $this->queueFactory->create()
                        ->getCollection()
                        ->addFieldToFilter('type', 'Item')
                        ->addFieldToFilter('entity_id', $product->getId())
                        ->getFirstItem();
                    if ($queue->getId()) {
                        /** contact exist in queue */
                        $queue = $this->queueFactory->create()->load($queue->getId());
                        $queue->setEnqueueTime(time());
                        $queue->save();
                        return $this;
                    }
                    $queue = $this->queueFactory->create();
                    $data = [
                        'type' => 'Item',
                        'entity_id' => $product->getId(),
                        'enqueue_time' => time(),
                        'priority' => 1,
                    ];
                    $queue->setData($data);
                    $queue->save();
                } else {
                    if (!$this->xeroHelper->isXeroConnectedByIds($product->getId(), $this->collectionFactory, 'entity_id')) {
                        return $this;
                    }
                    foreach ($product->getWebsiteIds() as $websiteId) {
                        if ($this->xeroHelper->isMultipleWebsiteEnable()) {
                            $this->xeroHelper->setScope('websites');
                            $this->xeroHelper->setScopeId($websiteId);
                        }

                        /** immediately mode */
                        $xml = $this->syncModel->addRecord($product);
                        if ($xml !== '') {
                            $xml = '<Items>' . $xml . '</Items>';
                        }
                        $this->syncModel->syncData($xml);

                        $transactionXml = $this->syncTransaction->addRecord($product);
                        $transactionXml = $this->syncTransaction->addOtherTags($transactionXml);
                        $this->syncTransaction->syncData($transactionXml);

                        if (!$this->xeroHelper->isMultipleWebsiteEnable()) {
                            break;
                        }
                    }
                }
                return $this;
            }
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('Xero Save Product Exception: '.$e->getMessage());
        } finally {
            return $this;
        }
    }
}
