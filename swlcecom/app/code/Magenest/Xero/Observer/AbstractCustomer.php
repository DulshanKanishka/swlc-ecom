<?php
namespace Magenest\Xero\Observer;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\Synchronization\Customer;
use Magenest\Xero\Model\QueueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Store\Model\StoreManager;
use Magenest\Xero\Model\Config;

abstract class AbstractCustomer extends Synchronize
{
    protected $pathEnable = 'magenest_xero_config/xero_contact/enabled';
    protected $pathSyncOption = 'magenest_xero_config/xero_contact/sync_mode';
    protected $pathTimeOption = 'magenest_xero_config/xero_contact/cron_time';

    /**
     * @var \Magenest\Xero\Model\Synchronization\Customer
     */
    protected $syncModel;

    protected $xeroHelper;

    public function __construct(
        QueueFactory $queueFactory,
        ScopeConfigInterface $config,
        StoreManager $storeManager,
        Customer $syncModel,
        Helper $helper
    ) {
        $this->syncModel = $syncModel;
        $this->xeroHelper = $helper;
        parent::__construct($queueFactory, $config, $storeManager);
    }

    public function execute(EventObserver $observer)
    {
        try {
            if ($this->enable) {
                /** @var CustomerInterface $customer */
                $customer = $observer->getCustomer();
                if ($this->syncMode == 1) {
                    /** cron job mode */
                    $queue = $this->queueFactory->create();
                    $data = [
                        'type' => 'Contact',
                        'entity_id' => $customer->getId(),
                        'enqueue_time' => time(),
                        'priority' => 1,
                    ];
                    $queue->setData($data);
                    $queue->save();
                } else {
                    if ($this->xeroHelper->isMultipleWebsiteEnable()){
                        $this->xeroHelper->setScope('websites');
                        $this->xeroHelper->setScopeId($customer->getWebsiteId());
                    }
                    if (!$this->xeroHelper->getConfig(Config::XML_PATH_XERO_IS_CONNECTED)) {
                        return $this;
                    }
                    /** immediately mode */
                    $xml = $this->syncModel->addRecord($customer);
                    if ($xml !== '') {
                        $xml = '<Contacts>' . $xml . '</Contacts>';
                    }
                    $this->syncModel->syncData($xml);
                }
                return $this;
            }
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('Xero Save Customer Exception: '.$e->getMessage());
        } finally {
            return $this;
        }
    }
}
