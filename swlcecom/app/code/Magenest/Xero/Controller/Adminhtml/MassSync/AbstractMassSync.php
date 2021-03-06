<?php
namespace Magenest\Xero\Controller\Adminhtml\MassSync;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magenest\Xero\Model\Helper;

abstract class AbstractMassSync extends Action
{
    protected $_config;

    protected $_filter;

    protected $_enable = "";

    protected $_helper;

    public function __construct(
        Context $context,
        ScopeConfigInterface $config,
        Filter $filter,
        Helper $helper
    ){
        $this->_config = $config;
        $this->_filter = $filter;
        $this->_helper = $helper;
        parent::__construct($context);
    }

    /**
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        $enable = $this->_config->getValue($this->_enable);
        if ($enable) {
            return parent::dispatch($request); // TODO: Change the autogenerated stub
        } else {
            return $this->handleUnauthorizedRequest();
        }
    }

    protected function handleUnauthorizedRequest()
    {
        if ($this->getRequest()->isAjax()) {
            $result = [
                'error' => true,
                'msg' => 'We could not handle your request. Please try again!'
            ];
            $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $response->setData($result);
            return $response;
        }
        $this->messageManager->addError('We could not handle your request. Please try again!');
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}