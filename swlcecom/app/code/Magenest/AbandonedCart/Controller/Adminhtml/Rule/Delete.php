<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Rule;

class Delete extends \Magenest\AbandonedCart\Controller\Adminhtml\Rule
{

    public function execute()
    {
        $params = $this->_request->getParams();
        try {
            /** @var \Magenest\AbandonedCart\Model\Rule $ruleModel */
            $ruleModel = $this->_ruleFactory->create();
            if (isset($params['id']) && $params['id']) {
                $ruleModel->load($params['id']);
                if ($this->geNotiLogId($ruleModel->getId())) {
                    $message = __('%1 is currently being used for a Notification Log. You must remove the message from this configuration before deleting the rule', $ruleModel->getName());
                    throw new \Exception($message);
                }
                $ruleModel->delete();
            }
            $this->messageManager->addSuccessMessage(__('The Rule has been deleted.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_logger->critical($exception->getMessage());
        }
        $this->_redirect('*/*/index');
    }
}