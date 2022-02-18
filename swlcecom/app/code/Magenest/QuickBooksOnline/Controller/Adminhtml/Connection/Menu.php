<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\QuickBooksOnline\Controller\Adminhtml\Connection;

use Magenest\QuickBooksOnline\Controller\Adminhtml\AbstractConnection;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magenest\QuickBooksOnline\Model\Authenticate;
use Magenest\QuickBooksOnline\Model\Client;

/**
 * Class Menu
 * @package Magenest\QuickBooksOnline\Controller\Adminhtml\Connection
 */
class Menu extends AbstractConnection
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * Disconnect constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Authenticate $authenticate
     * @param Client $client
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Authenticate $authenticate,
        Client $client
    ) {
        parent::__construct($context, $resultPageFactory, $authenticate);
        $this->client = $client;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $menu = $this->client->widgetMenu();
        $this->_response->setBody($menu);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('/');
        
        return $resultRedirect;
    }
}
