<?php
namespace Magenest\Xero\Controller\Adminhtml\Tax;

use Magenest\Xero\Model\PaymentMappingFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\CacheInterface;

class UpdateTaxRates extends \Magento\Backend\App\Action
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
        $websiteId = $this->_request->getParam('website_id') ? : 0;
        $this->_cache->remove('XERO_TAX_RATES_'.$websiteId);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::xero');
    }
}
