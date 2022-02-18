<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Controller\Adminhtml\Connection;

use Magenest\QuickBooksOnline\Controller\Adminhtml\AbstractConnection;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Begin
 *
 * @package Magenest\QuickBooksOnline\Controller\Adminhtml\Connection
 */
class Begin extends AbstractConnection
{
    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $websiteId = $this->getRequest()->getParam('website');
        $qboMode = $this->getRequest()->getParam('qbo_mode');
//        $url = $this->getUrl(
//            '*/*/success',
//            [
//                'website' => $websiteId,
//                'qbo_mode' => $qboMode
//            ]
//        );
        $url = $this->getUrl(
            '*/*/success'
        );
        $callbackUrl = rtrim($url, "/");

        /** @var \Magento\Framework\Controller\Result\Redirect $redirectPage */
        $redirectPage = $this->resultFactory->create('redirect');
        $redirectPage->setPath('/');

        try {
            $link = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Store\Model\StoreManagerInterface')
                    ->getStore()->getBaseUrl() . 'qbonline/connection/success';
            $redirectUrl = $this->authenticate->redirectUrl($link);
            $this->_redirect($redirectUrl);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirectPage;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirectPage;
        }
    }
}
