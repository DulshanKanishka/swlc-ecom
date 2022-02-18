<?php
namespace Magenest\Xero\Controller\Adminhtml\Payment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\CacheInterface;

class UpdateBankAccounts extends \Magento\Backend\App\Action
{

    protected $_cache;

    public function __construct(
        Context $context,
        CacheInterface $cache
    ) {
        parent::__construct($context);
        $this->_cache = $cache;
    }

    public function execute()
    {
        $websiteId = $this->getRequest()->getParam('website_id');
        $this->_cache->remove('XERO_BANK_ACCOUNTS_'.$websiteId);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::xero');
    }
}
