<?php

namespace Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;

use Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;
use Magento\Framework\Exception\LocalizedException;

class MassAction extends Rule
{
    public function execute()
    {
        $ids = $this->getRequest()->getParam('rules');
        $action = $this->getRequest()->getParam('action');
        if ($ids && in_array($action, ['activate', 'inactivate', 'delete'])) {
            try {
                $status = -1;
                switch ($action) {
                    case 'delete':
                        $collection = $this->ruleCollectionFactory->create();
                        $collection->addFieldToFilter('rule_id', ['in' => $ids]);
                        $collection->walk($action);
                        $status = -1;
                        $message = __('You deleted the rule(s).');
                        break;
                    case 'activate':
                        $status = 1;
                        $message = __('You activated the rule(s).');
                        break;
                    case 'inactivate':
                        $status = 0;
                        $message = __('You deactivated the rule(s).');
                        break;
                }

                if ($status > -1) {
                    $this->ruleFactory->create()->massChangeStatus($ids, $status);
                }

                $this->messageManager->addSuccessMessage($message);
                $this->_redirect('*/*/');
                return;
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage(__('We can\'t delete/activate/deactivate rule(s) right now. Please review the log and try again.') . $e->getMessage());
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find a rule(s) to delete/activate/deactivate.'));
        $this->_redirect('*/*/');
    }
}
