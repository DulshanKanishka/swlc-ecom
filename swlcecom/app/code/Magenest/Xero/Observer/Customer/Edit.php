<?php
namespace Magenest\Xero\Observer\Customer;

use Magenest\Xero\Observer\AbstractCustomer;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magenest\Xero\Model\Config;

class Edit extends AbstractCustomer
{
    public function execute(EventObserver $observer)
    {
        try {
            if ($this->enable) {
                /** @var CustomerInterface $customer */
                $customer = $observer->getCustomerAddress()->getCustomer();

                if ($this->syncMode == 1) {
                    /** cron job mode */
                    $queue = $this->queueFactory->create()
                        ->getCollection()
                        ->addFieldToFilter('type', 'Contact')
                        ->addFieldToFilter('entity_id', $customer->getId())
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
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('Xero Save Contact Exception: '.$e->getMessage());
        } finally {
            return $this;
        }
    }
}
