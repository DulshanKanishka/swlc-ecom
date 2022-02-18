<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Blacklist;

class Delete extends \Magenest\AbandonedCart\Controller\Adminhtml\Blacklist
{
    public function execute()
    {
        $params = $this->_request->getParams();
        try {
            /** @var \Magenest\AbandonedCart\Model\Rule $ruleModel */
            $blacklistModel = $this->_blacklistFactory->create();
            if (isset($params['id']) && $params['id']) {
                $blacklistModel->load($params['id']);
                $blacklistModel->delete();
            }
            $this->messageManager->addSuccessMessage(__('The record has been deleted.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_logger->critical($exception->getMessage());
        }
        $this->_redirect('*/*/index');
    }
}