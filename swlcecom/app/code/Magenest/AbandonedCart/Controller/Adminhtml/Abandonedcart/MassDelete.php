<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Abandonedcart;

use Magenest\AbandonedCart\Model\ResourceModel\AbandonedCart\Collection;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class MassDelete extends \Magenest\AbandonedCart\Controller\Adminhtml\Abandonedcart
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
            /** @var \Magenest\AbandonedCart\Model\AbandonedCart $abandonedCartModel */
            $abandonedCartModel = $this->_abandonedCartFactory->create();
            $abandonedCartModel->deleteMultiple($abandonedCartIds);
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