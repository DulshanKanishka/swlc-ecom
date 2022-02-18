<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Rule;

class ChangeStatus extends \Magenest\AbandonedCart\Controller\Adminhtml\Rule
{

    public function execute()
    {
        try {
            $collection = $this->_filer->getCollection($this->_collectionFactory->create());
            $count      = 0;
            foreach ($collection as $rule) {
                $status = $rule->getStatus();
                if ($status == 1) {
                    $rule->setStatus(0);
                } else {
                    $rule->setStatus(1);
                }
                $rule->save();
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
}