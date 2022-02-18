<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Controller\Adminhtml\Connection;

use Magenest\QuickBooksOnline\Controller\Adminhtml\AbstractConnection;
use Magenest\QuickBooksOnline\Model\Config;
use Magento\Backend\App\Action\Context;
use Magento\Config\Model\Config as ConfigModel;
use Magento\Framework\View\Result\PageFactory;
use Magenest\QuickBooksOnline\Model\Authenticate;
use Magenest\QuickBooksOnline\Model\Client;
use Magenest\QuickBooksOnline\Model\OauthFactory;
use Magenest\QuickBooksOnline\Model\AccountFactory;
use Magenest\QuickBooksOnline\Model\PaymentMethodsFactory;
use Magenest\QuickBooksOnline\Model\TaxFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class Disconnect
 * @package Magenest\QuickBooksOnline\Controller\Adminhtml\Connection
 */
class Disconnect extends AbstractConnection
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ConfigModel
     */
    protected $config;

    /**
     * @var OauthFactory
     */
    protected $oauth;

    /**
     * @var AccountFactory
     */
    protected $account;

    /**
     * @var PaymentMethodsFactory
     */
    protected $paymentMethod;

    /**
     * @var TaxFactory
     */
    protected $taxCode;

    /**
     * @var WriterInterface
     */
    protected $writer;

    /**
     * Disconnect constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Authenticate $authenticate
     * @param Client $client
     * @param ConfigModel $config
     * @param OauthFactory $oauthFactory
     * @param AccountFactory $account
     * @param PaymentMethodsFactory $paymentMethod
     * @param TaxFactory $taxCode
     * @param WriterInterface $writer
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Authenticate $authenticate,
        Client $client,
        ConfigModel $config,
        OauthFactory $oauthFactory,
        AccountFactory $account,
        PaymentMethodsFactory $paymentMethod,
        TaxFactory $taxCode,
        WriterInterface $writer
    ) {
        parent::__construct($context, $resultPageFactory, $authenticate);
        $this->client        = $client;
        $this->config        = $config;
        $this->oauth         = $oauthFactory;
        $this->account       = $account;
        $this->paymentMethod = $paymentMethod;
        $this->taxCode       = $taxCode;
        $this->writer        = $writer;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
//            $this->client->disconnect();
            $company = (int)$this->config->getConfigDataValue(Config::XML_PATH_QBONLINE_COMPANY_ID);
            $model   = $this->oauth->create()->load($company, 'qb_realm');
            $model->delete();
//            $this->config->setDataByPath(Config::XML_PATH_QBONLINE_IS_CONNECTED, 0);
//            $this->config->setDataByPath(Config::XML_PATH_QBONLINE_COMPANY_ID, null);
//            $this->config->save();
            $this->writer->delete(Config::XML_PATH_QBONLINE_IS_CONNECTED);
            $this->writer->delete(Config::XML_PATH_QBONLINE_COMPANY_ID);
            $this->writer->delete(Config::XML_PATH_ADJUSTMENT_ID);
            $this->removeCompanyData();
            $this->refreshCache();
            $this->messageManager->addSuccessMessage(__('You\'re disconnected from QuickBooks Online.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $referUrl     = $this->_redirect->getRefererUrl();
        $redirectPage = $this->resultRedirectFactory->create();

        return $redirectPage->setUrl($referUrl);
    }

    /**
     *
     */
    protected function refreshCache()
    {
        $_cacheTypeList     = $this->_objectManager->create(\Magento\Framework\App\Cache\TypeListInterface::class);
        $_cacheFrontendPool = $this->_objectManager->create(\Magento\Framework\App\Cache\Frontend\Pool::class);
        $types              = ['config', 'full_page'];
        foreach ($types as $type) {
            $_cacheTypeList->cleanType($type);
        }
        foreach ($_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    /**
     * Delete old company's accounts, tax codes and payment methods mappings upon disconnect
     */
    protected function removeCompanyData()
    {
        $this->account->create()->getCollection()->walk('delete');
        $this->paymentMethod->create()->getCollection()->walk('delete');
        $this->taxCode->create()->getCollection()->walk('delete');
    }
}
