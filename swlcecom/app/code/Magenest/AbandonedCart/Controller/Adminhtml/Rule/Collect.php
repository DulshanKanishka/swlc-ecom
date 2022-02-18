<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Rule;

class Collect extends \Magenest\AbandonedCart\Controller\Adminhtml\Rule
{

    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $this->_cronJob->collectAbandonedCartsForTestCampaign($id);
            }
            $this->messageManager->addSuccessMessage(__('Collect abandoned cart from quote successfully.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $this->resultRedirectFactory->create()->setPath(
            '*/*/edit',
            ['id' => $id, 'back' => null, '_current' => true]
        );
    }
}