<?php
namespace Magenest\Xero\Controller\Adminhtml\App;

use Magenest\Xero\Helper\Signature;
use Magenest\Xero\Model\Cache;
use Magento\Backend\App\Action\Context;
use Magenest\Xero\Model\XeroClient;
use Magenest\Xero\Model\Synchronization\Account as AccountSync;
use Magenest\Xero\Model\Synchronization\Customer as CustomerSync;
use Magenest\Xero\Model\Synchronization\Item;
use Magento\Config\Model\ResourceModel\Config as ConfigModel;
use Magenest\Xero\Model\Config as XeroConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magenest\Xero\Model\Helper;

/**
 * Class Connect
 * @package Magenest\Xero\Controller\Adminhtml\App
 */
class Connect extends \Magento\Backend\App\Action
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

    /**
     * @var Item
     */
    protected $syncItem;

    /**
     * @var CustomerSync
     */
    protected $customerSync;

    protected $_xeroHelper;

    /**
     * Connect constructor.
     * @param Context $context
     * @param ConfigModel $configModel
     * @param XeroClient $xeroClient
     * @param Item $syncItem
     * @param CustomerSync $customerSync
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        ConfigModel $configModel,
        XeroClient $xeroClient,
        Item $syncItem,
        CustomerSync $customerSync,
        Helper $helper
    ) {
        $this->customerSync = $customerSync;
        $this->xeroClient = $xeroClient;
        $this->configModel = $configModel;
        $this->syncItem = $syncItem;
        $this->_xeroHelper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = [
            'error' => true,
            'description' => '',
        ];
        try {
            if ($this->getRequest()->isPost()) {
                $website = $this->getRequest()->getParam('website');
                if ($website) {
                    $scope = ScopeInterface::SCOPE_WEBSITES;
                    $id = $website;
                    $this->_xeroHelper->setScope($scope);
                    $this->_xeroHelper->setScopeId($id);
                } else {
                    $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                    $id = 0;
                }
                $data = $this->getRequest()->getPostValue();
                $params = [
                    'oauth_consumer_key' => $data['consumer_key'],
                    'oauth_token' => $data['consumer_key'],
                ];
                $result = $this->xeroClient->checkConnect($params);
                $this->configModel->saveConfig(Signature::PATH_CONSUMER_KEY, $data['consumer_key'], $scope, $id);
                $this->configModel->saveConfig(XeroConfig::XML_PATH_XERO_IS_CONNECTED, 1, $scope, $id);
                $this->syncAdditionalItem($data['consumer_key']);
                $this->syncTransactionContact($data['consumer_key']);
                Cache::refreshCache();
                $this->messageManager->addSuccessMessage(__($result['description']));
            }
        } catch (\Exception $e) {
            $result = [
                'error' => true,
                'description' => __($e->getMessage()),
            ];
        }

        /** @var \Magento\Framework\Controller\Result\Json $jsonFactory */
        $jsonFactory = $this->resultFactory->create('json');

        return $jsonFactory->setData($result);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::config_xero');
    }


    protected function syncAdditionalItem($token)
    {
        $this->syncItem->getClient()->getSignature()->setConsumerKey($token);
//        $xml = $this->syncItem->getTaxItemXml();
        $xml = $this->syncItem->getShippingItemXml();
        $xml = '<Items>'.$xml.'</Items>';
        $this->syncItem->syncData($xml);
    }

    protected function syncTransactionContact($token)
    {
        $this->customerSync->getClient()->getSignature()->setConsumerKey($token);
        $xml = $this->customerSync->getTransactionContactXml();
        $this->customerSync->syncData($xml);
    }
}
