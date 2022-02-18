<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Unsubscribe;

use Magenest\AbandonedCart\Model\ResourceModel\Unsubscribe;
use Magento\Ui\Component\MassAction\Filter;

class ChangeStatus extends \Magenest\AbandonedCart\Controller\Adminhtml\Unsubscribe
{

    public function execute()
    {
        try {
            $collection = $this->_filer->getCollection($this->_collectionFactory->create());
            $count      = 0;
            foreach ($collection as $record) {
                $status = $record->getUnsubscriberStatus();
                if ($status == \Magenest\AbandonedCart\Model\Config\Source\UnsubscriberStatus::SUBSCRIBED) {
                    $record->setUnsubscriberStatus(\Magenest\AbandonedCart\Model\Config\Source\UnsubscriberStatus::UNSUBSCRIBED);
                    $this->cancelNotiToCustomer($record->getData('unsubscriber_email'));
                } else {
                    $record->setUnsubscriberStatus(\Magenest\AbandonedCart\Model\Config\Source\UnsubscriberStatus::SUBSCRIBED);
                }
                $record->save();
                $count++;
            }
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been changed.', $count)
            );
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_logger->critical($exception->getMessage());
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }

    public function cancelNotiToCustomer($email)
    {
        $collections = $this->_logContentFactory->create()
            ->getCollection()
            ->addFieldToFilter(
                'recipient_adress',
                $email
            )->addFieldToFilter(
                'status',
                \Magenest\AbandonedCart\Model\Config\Source\Mail::STATUS_QUEUED
            );
        if (count($collections)) {
            foreach ($collections as $collection) {
                $collection->setStatus(\Magenest\AbandonedCart\Model\Config\Source\Mail::STATUS_CANCELLED);
                $collection->setLog(__('Admin change status to unsubscribe email'));
                $collection->save();
            }
        }
    }
}