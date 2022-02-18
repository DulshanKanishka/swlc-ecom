<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\QuickBooksOnline\Controller\Adminhtml\Category;

use Magenest\QuickBooksOnline\Controller\Adminhtml\Category as CategoryController;

/**
 * Class Index
 * @package Magenest\QuickBooksOnline\Controller\Adminhtml\Category
 */
class Index extends CategoryController
{
    /**
     * execute the action
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->_initAction();
        $resultPage->getConfig()->getTitle()->prepend((__('Mapping Categories')));
        
        return $resultPage;
    }
}
