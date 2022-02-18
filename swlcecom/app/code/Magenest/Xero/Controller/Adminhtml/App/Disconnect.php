<?php
namespace Magenest\Xero\Controller\Adminhtml\App;

use Magento\Backend\App\Action\Context;
use Magenest\Xero\Model\XeroClient;
use Magento\Config\Model\ResourceModel\Config as ConfigModel;
use Magenest\Xero\Model\Helper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Disconnect
 * @package Magenest\Xero\Controller\Adminhtml\App
 */
class Disconnect extends \Magento\Backend\App\Action
{
    /**@#+
     * XML Path
     */
    const XML_PATH_XERO_IS_CONNECTED = 'magenest_xero_config/xero_api/is_connected';

    /**
     * @var ConfigModel
     */
    protected $configModel;

    /**
     * @var XeroClient
     */
    protected $xeroClient;

    protected $_xeroHelper;

    /**
     * Disconnect constructor.
     * @param Context $context
     * @param ConfigModel $configModel
     * @param XeroClient $xeroClient
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        ConfigModel $configModel,
        XeroClient $xeroClient,
        Helper $helper
    ) {
        $this->xeroClient = $xeroClient;
        $this->configModel = $configModel;
        $this->_xeroHelper = $helper;
        parent::__construct($context);
    }

    /**
     *
     */
    public function execute()
    {
        $scopeId = $this->getRequest()->getParam('website');
        if ($scopeId) {
            $this->_xeroHelper->setScope(ScopeInterface::SCOPE_WEBSITES);
            $this->_xeroHelper->setScopeId($scopeId);
        }
        try {
            $this->xeroClient->disconnectApp();
            $this->messageManager->addNoticeMessage('You have disconnected from Xero.');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::config_xero');
    }
}
