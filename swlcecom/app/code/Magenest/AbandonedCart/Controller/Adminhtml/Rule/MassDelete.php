<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Rule;

use Magenest\AbandonedCart\Model\ResourceModel\Rule;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends \Magenest\AbandonedCart\Controller\Adminhtml\Rule
{

    public function execute()
    {
        try {
            $collection = $this->_filer->getCollection($this->_collectionFactory->create());
            $count      = 0;
            $ruleIds    = [];
            foreach ($collection->getItems() as $item) {
                if ($this->geNotiLogId($item->getId())) {
                    $message = __('%1 is currently being used for a Notification Log. You must remove the message from this configuration before deleting the rule', $item->getName());
                    throw new \Exception($message);
                }
                $ruleIds[] = $item->getId();
                $count++;
            }
            /** @var \Magenest\AbandonedCart\Model\Rule $ruleModel */
            $ruleModel = $this->_ruleFactory->create();
            $ruleModel->deleteMultiple($ruleIds);
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $count)
            );
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_logger->critical($exception->getMessage());
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}