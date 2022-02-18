<?php
namespace Magenest\Xero\Observer\Customer\Adminhtml;

use Magenest\Xero\Observer\AbstractCustomer;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer as EventObserver;

class Delete extends AbstractCustomer
{
    public function execute(EventObserver $observer)
    {
        try {
            if ($this->enable) {
                /** @var CustomerInterface $customer */
                $customer = $observer->getCustomer();

                if ($this->syncMode == 1) {
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
                }
                parent::execute($observer);
            }
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('Xero Save Contact Exception: '.$e->getMessage());
        } finally {
            return $this;
        }
    }
}
