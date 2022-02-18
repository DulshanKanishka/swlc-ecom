<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Abandonedcart;

class Delete extends \Magenest\AbandonedCart\Controller\Adminhtml\Abandonedcart
{

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        try {
            /** @var \Magenest\AbandonedCart\Model\AbandonedCart $abandonedCartModel */
            $abandonedCartModel = $this->_abandonedCartFactory->create();
            if (isset($params['id']) && $params['id']) {
                $abandonedCartModel->load($params['id']);
                $abandonedCartModel->delete();
            }
            $this->messageManager->addSuccessMessage(__('The Abandoned Cart has been deleted.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_logger->critical($exception->getMessage());
        }
        $this->_redirect('*/*/index');
    }
}