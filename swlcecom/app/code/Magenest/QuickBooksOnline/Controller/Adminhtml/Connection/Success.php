<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\QuickBooksOnline\Controller\Adminhtml\Connection;

use Magenest\QuickBooksOnline\Controller\Adminhtml\AbstractConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\Exception;

/**
 * Class Success
 *
 * @package Magenest\QuickBooksOnline\Controller\Adminhtml\Connection
 */
class Success extends AbstractConnection
{
    
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magenest_QuickBooksOnline::connection');
        $resultPage->getConfig()->getTitle()->prepend(__('QuickBooks Online'));
        
        try {
            $code = $this->getRequest()->getParam('code');
            $state = $this->getRequest()->getParam('state');

            if (strcmp($state, "RandomState") != 0) {
                throw new Exception(__("The state is not correct from Intuit Server. Consider your app is hacked."));
            }
//            $this->authenticate->retrieveAccessToken("authorization_code" , $code);

            $this->authenticate->getAccessToken("authorization_code", $code);

            $this->refreshCache();
            $this->messageManager->addSuccessMessage(__('You\'re connected to QuickBooks Online. Please go to Configuration and sync Account first.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        
        return $resultPage;
    }

    /**
     * refresh cache
     */
    protected function refreshCache()
    {
        $_cacheTypeList = $this->_objectManager->create(\Magento\Framework\App\Cache\TypeListInterface::class);
        $_cacheFrontendPool = $this->_objectManager->create(\Magento\Framework\App\Cache\Frontend\Pool::class);
        $types = ['config','full_page'];
        foreach ($types as $type) {
            $_cacheTypeList->cleanType($type);
        }
        foreach ($_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }
}
