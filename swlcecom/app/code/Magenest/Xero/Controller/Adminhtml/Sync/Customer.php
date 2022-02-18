<?php
namespace Magenest\Xero\Controller\Adminhtml\Sync;

use Magenest\Xero\Model\Config;
use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\Synchronization;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Index
 * @package Magenest\Xero\Controller\Adminhtml\Sync
 */
class Customer extends \Magento\Backend\App\Action
{
    /**
     * @var Synchronization\Customer
     */
    protected $syncCustomer;

    protected $customer;

    protected $xeroHelper;

    /**
     * Customer constructor.
     * @param Context $context
     * @param Synchronization\Customer $syncCustomer
     * @param CustomerFactory $customerFactory
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        Synchronization\Customer $syncCustomer,
        CustomerFactory $customerFactory,
        Helper $helper
    ) {
        $this->syncCustomer = $syncCustomer;
        $this->customer = $customerFactory;
        $this->xeroHelper = $helper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        try {
            $customerId = $this->getRequest()->getParam('id');
            $customer = $this->customer->create()->load($customerId);
            if ($this->xeroHelper->isMultipleWebsiteEnable()){
                $this->xeroHelper->setScope('websites');
                $this->xeroHelper->setScopeId($customer->getWebsiteId());
            }
            if (!$this->xeroHelper->getConfig(Config::XML_PATH_XERO_IS_CONNECTED)) {
                $this->messageManager->addErrorMessage('Please connect the integration to your Xero account first!');
                return $resultRedirect;
            }
            if ($customer->getId()) {
                /** sync customers */
                $xml = $this->syncCustomer->addRecord($customer);
                $xml = '<Contacts>' . $xml . '</Contacts>';
                $this->syncCustomer->syncData($xml);
                $this->messageManager->addSuccess(
                    __('Sync process complete, please check out Logs for results')
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(
                __('Something happen during syncing process. Detail: ' . $e->getMessage())
            );
        }


        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::config_xero');
    }
}
