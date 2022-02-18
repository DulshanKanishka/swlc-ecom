<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Rule;

class TestCampaignGrid extends \Magenest\AbandonedCart\Controller\Adminhtml\Rule
{

    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->getLayout()->getMessagesBlock()->setMessages($this->messageManager->getMessages(true));
        $this->_view->renderLayout();
//        return $this;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_AbandonedCart::rule');
    }
}
