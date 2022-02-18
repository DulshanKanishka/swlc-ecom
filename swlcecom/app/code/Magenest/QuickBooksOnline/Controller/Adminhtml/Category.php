<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\QuickBooksOnline\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magenest\QuickBooksOnline\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Class Category
 * @package Magenest\QuickBooksOnline\Controller\Adminhtml
 */
abstract class Category extends Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * Category collection factory
     *
     * @var \Magenest\QuickBooksOnline\Model\ResourceModel\Category\CollectionFactory
     */
    protected $_categoryFactory;

    /**
     * Map Collection factory
     *
     * @var \Magenest\QuickBooksOnline\Model\CategoryFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param CategoryFactory $categoryFactory
     * @param CategoryCollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        CategoryFactory $categoryFactory,
        CategoryCollectionFactory $collectionFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->_categoryFactory = $categoryFactory;
        $this->_collectionFactory = $collectionFactory;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Init actions
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magenest_QuickBooksOnline::categories')
            ->addBreadcrumb(__('View Categories'), __('View Categories'));
        $resultPage->getConfig()->getTitle()->set(__('View Categories'));

        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_QuickBooksOnline::categories');
    }
}
