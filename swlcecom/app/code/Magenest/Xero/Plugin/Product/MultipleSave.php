<?php
namespace Magenest\Xero\Plugin\Product;

use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Model\QueueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManager;
use Magento\Catalog\Model\ProductFactory;

class MultipleSave
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

    protected $productFactory;

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

    public function __construct
    (
        QueueFactory $queueFactory,
        ScopeConfigInterface $config,
        StoreManager $storeManager,
        Synchronization\Item $syncModel,
        Synchronization\BankTransaction $syncTransaction,
        ProductFactory $productFactory
    )
    {
        $this->syncModel = $syncModel;
        $this->syncTransaction = $syncTransaction;
        $this->productFactory = $productFactory;
        $this->queueFactory = $queueFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->getSyncSettings();
    }

    public function afterUpdateAttributes(\Magento\Catalog\Model\Product\Action $subject, $result)
    {
        try {
            $productIds = $result->getProductIds();

            if ($this->enable) {
                $xml = "";
                $transactionXml = "";
                foreach($productIds as $id) {
                    if ($this->syncMode == 1) {
                        /** cron job mode */
                        $queue = $this->queueFactory->create()
                            ->getCollection()
                            ->addFieldToFilter('type', 'Item')
                            ->addFieldToFilter('entity_id', $id)
                            ->getFirstItem();
                        if ($queue->getId()) {
                            /** contact exist in queue */
                            $queue = $this->queueFactory->create()->load($id);
                            $queue->setEnqueueTime(time());
                            $queue->save();
                        }
                        $queue = $this->queueFactory->create();
                        $data = [
                            'type' => 'Item',
                            'entity_id' => $id,
                            'enqueue_time' => time(),
                            'priority' => 1,
                        ];
                        $queue->setData($data);
                        $queue->save();
                    } else {
                        $product = $this->productFactory->create()->load($id);
                        $xml .= $this->syncModel->addRecord($product);

                        $transactionXml = $this->syncTransaction->addRecord($product);
                        $transactionXml = $this->syncTransaction->addOtherTags($transactionXml);
                    }
                }
                if ($this->syncMode != 1) {
                    if ($xml !== '') {
                        $xml = '<Items>' . $xml . '</Items>';
                    }
                    $this->syncModel->syncData($xml);
                    $this->syncTransaction->syncData($transactionXml);
                }
            }
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('Xero Save Product Exception: '.$e->getMessage());
        }
        return $result;
    }

    protected function getSyncSettings()
    {
        $this->enable = $this->config->getValue($this->pathEnable);
        $this->syncMode = $this->config->getValue($this->pathSyncOption);
        $this->syncTime = $this->config->getValue($this->pathTimeOption);
    }
}