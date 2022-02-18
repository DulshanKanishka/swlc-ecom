<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Abandonedcart;

class MassStatus extends \Magenest\AbandonedCart\Controller\Adminhtml\Abandonedcart
{

    public function execute()
    {
        try {
            $collections      = $this->_filter->getCollection($this->_collectionFactory->create());
            $count            = 0;
            $abandonedCartIds = [];
            foreach ($collections as $collection) {
                $abandonedCartIds[] = $collection->getId();
                $count++;
            }
            if (count($abandonedCartIds)) {
                $this->updateStatus($abandonedCartIds);
                $this->cancelNotiToCustomer($abandonedCartIds);
            }
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been Set Not AbandonedCart.', $count)
            );
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }

    public function updateStatus($abacarIds)
    {
        foreach ($abacarIds as $id) {
            $model = $this->_abandonedCartFactory->create();
            $this->_abandonedCartResource->load($model, $id, 'id');
            $model->setStatus(
                \Magenest\AbandonedCart\Model\AbandonedCart::STATUS_NOT_ABANDONED
            );
            $this->_abandonedCartResource->save($model);
        }
    }

    public function cancelNotiToCustomer($abacarIds)
    {
        $logCollections = $this->_logContentFactory->create()
            ->getCollection()
            ->addFieldToFilter(
                'abandonedcart_id',
                [$abacarIds]
            )->addFieldToFilter(
                'status',
                \Magenest\AbandonedCart\Model\Config\Source\Mail::STATUS_QUEUED
            );
        if (count($logCollections)) {
            foreach ($logCollections as $logCollection) {
                $logCollection->setStatus(\Magenest\AbandonedCart\Model\Config\Source\Mail::STATUS_CANCELLED);
                $logCollection->setLog(__('Admin update cart is not abandoned'));
                $logCollection->save();
            }
        }
    }
}