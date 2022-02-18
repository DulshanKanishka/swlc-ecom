<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Rule;

class Edit extends \Magenest\AbandonedCart\Controller\Adminhtml\Rule
{

    public function execute()
    {
        try {
            $id            = $this->getRequest()->getParam('id');
            $saleRuleModel = $this->_objectManager->create('Magento\SalesRule\Model\Rule');
            /** @var \Magenest\AbandonedCart\Model\Rule $ruleModel */
            $ruleModel = $this->_ruleFactory->create();

            if ($id) {
                $ruleModel->load($id);
                $saleRuleModel->setData('conditions_serialized', $ruleModel->getData('conditions_serialized'));
                if (!$ruleModel->getId()) {
                    $this->messageManager->addErrorMessage(__('This Rule doesn\'t exist'));
                    $resultRedirect = $this->resultRedirectFactory->create();
                    return $resultRedirect->setPath('*/*/index');
                }
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_logger->critical($exception->getMessage());
        }
        $this->_coreRegistry->register('current_promo_sale_rule', $saleRuleModel);
        $this->_coreRegistry->register('abandonedcart_rule', $ruleModel);
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend($ruleModel->getId() ? __($ruleModel->getName()) : __('New Rule'));
        return $resultPage;
    }
}