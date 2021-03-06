<?php

/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Controller\Index;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Test extends \Magestore\Storepickup\Controller\Index
{
    /**
     * @var \Magestore\Storepickup\Helper\Data
     */
    protected $_helper;

    /**
     * Test constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magestore\Storepickup\Model\SystemConfig $systemConfig
     * @param \Magestore\Storepickup\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magestore\Storepickup\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magestore\Storepickup\Model\SystemConfig $systemConfig,
        \Magestore\Storepickup\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magestore\Storepickup\Helper\Data $helper
    ){
        parent::__construct($context, $systemConfig, $storeCollectionFactory, $coreRegistry, $jsonHelper);
        $this->_helper = $helper;
    }

    /**
     * Execute action.
     */
    public function execute()
    {
        $stores = $this->_objectManager->create('Magestore\Storepickup\Model\Store')->getCollection();
        $this->_helper->filterStoreByItemInCart($stores);

        if (!$this->_systemConfig->isEnableFrontend()) {
            return $this->_getResultRedirectNoroute();
        }

        $storepickupId = $this->getRequest()->getParam('storepickup_id');

        /** @var \Magestore\Storepickup\Model\Store $store */
        $store = $this->_objectManager->create('Magestore\Storepickup\Model\Store')->load($storepickupId);

        if (!$store->getId() || !$store->isEnabled()) {
            return $this->_getResultRedirectNoroute();
        }

        /*
         * load base image of store
         */

        $this->_coreRegistry->register('storepickup_store', $store);

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_initResultPage();
        $resultPage->getConfig()->getTitle()->set($store->getMetaTitle());
        $resultPage->getConfig()->setDescription($store->getMetaDescription());
        $resultPage->getConfig()->setKeywords($store->getMetaKeywords());

        return $resultPage;
    }
}
