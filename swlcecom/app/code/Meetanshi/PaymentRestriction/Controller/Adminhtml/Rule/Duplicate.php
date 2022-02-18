<?php

namespace Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;

use Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;
use Magento\Framework\Exception\LocalizedException;

class Duplicate extends Rule
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('rule_id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('Please select a rule to duplicate.'));
            return $this->_redirect('*/*');
        }
        try {
            $model = $this->ruleFactory->create()->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This item no longer exists.'));
                $this->_redirect('*/*');
                return;
            }

            $rule = clone $model;
            $rule->setIsActive(0);
            $rule->setId(null);
            $rule->save();

            $this->messageManager->addSuccessMessage(__('The rule has been duplicated. Please feel free to activate it.'));
            return $this->_redirect('*/*/edit', ['id' => $rule->getId()]);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_redirect('*/*');
            return;
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while saving the item data. Please review the error log.'));
            $this->_redirect('*/*');
            return;
        }
    }
}
