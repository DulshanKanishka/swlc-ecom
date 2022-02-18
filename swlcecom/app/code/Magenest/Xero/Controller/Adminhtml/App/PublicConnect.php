<?php
namespace Magenest\Xero\Controller\Adminhtml\App;

use Magenest\Xero\Helper\Signature;
use Magenest\Xero\Model\Cache;
use Magenest\Xero\Model\XeroClient;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magenest\Xero\Model\Synchronization\Account as AccountSync;
use Magenest\Xero\Model\Synchronization\Customer as CustomerSync;
use Magenest\Xero\Model\Synchronization\Item;
use Magento\Config\Model\ResourceModel\Config as ConfigModel;
use Magento\Store\Model\ScopeInterface;
use Magenest\Xero\Model\Helper;

/**
 * Class Index
 * @package Magenest\Xero\Controller\Adminhtml\App
 */
class PublicConnect extends \Magento\Backend\App\Action
{

    /**
     * @var ConfigModel
     */
    protected $configModel;

    /**
     * @var XeroClient
     */
    protected $xeroClient;

    /**
     * @var AccountSync
     */
    protected $account;

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
     * ConnectPublicApp constructor.
     * @param Context $context
     * @param XeroClient $xeroClient
     * @param ConfigModel $configModel
     * @param XeroClient $xeroClient
     * @param AccountSync $account
     * @param Item $syncItem
     * @param CustomerSync $customerSync
     */
    public function __construct(
        Context $context,
        XeroClient $xeroClient,
        ConfigModel $configModel,
        AccountSync $account,
        Item $syncItem,
        CustomerSync $customerSync,
        Helper $helper
    ) {
        $this->account = $account;
        $this->customerSync = $customerSync;
        $this->xeroClient = $xeroClient;
        $this->configModel = $configModel;
        $this->syncItem = $syncItem;
        $this->_xeroHelper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {

        $redirect = $this->resultRedirectFactory->create();
        try {
            if ($oauth = $this->_session->getOauth()) {
                $oauth = array_merge($oauth, $this->getRequest()->getParams());
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
                $this->_session->unsetData('oauth');
                if (!isset($oauth['oauth_verifier'])) {
                    throw new \Exception('Failed to get Oauth Verifier');
                }
                $token = $this->xeroClient->getAccessToken($oauth);
                $this->xeroClient->checkConnect([
                    'oauth_token_secret' => $token['oauth_token_secret'],
                    'oauth_token' => $token['oauth_token'],
                ]);
                $this->configModel->saveConfig(Signature::PATH_OAUTH_TOKEN, $token['oauth_token'], $scope, $id);
                $this->configModel->saveConfig(Signature::PATH_OAUTH_TOKEN_SECRET, $token['oauth_token_secret'], $scope, $id);
                $this->configModel->saveConfig(Signature::PATH_XERO_IS_CONNECTED, 1, $scope, $id);
                $this->syncTransactionContact($token);
                Cache::refreshCache();
                $this->messageManager->addSuccess('You have connected to Xero using Public App. Access is available in 30 minutes.');
                $redirect->setUrl($this->getUrl('admin/system_config/edit/section/magenest_xero_config/'));
            } else {
                $oauth = $this->xeroClient->getRequestToken($this->_url->getCurrentUrl());
                $this->_session->setOauth($oauth);
                $redirect->setUrl('https://api.xero.com/oauth/Authorize?oauth_token=' . $oauth['oauth_token']);
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(html_entity_decode($e->getMessage()));
            $redirect->setUrl($this->getUrl('admin/system_config/edit/section/magenest_xero_config/'));
        }
        return $redirect;
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
//        $xml = $this->syncItem->getTaxItemXml();
        $xml = $this->syncItem->getShippingItemXml();
        $xml = '<Items>'.$xml.'</Items>';
        $this->syncItem->getClient()->getSignature()->setOauthSecret($token['oauth_token_secret']);
        $this->syncItem->getClient()->getSignature()->setOauthToken($token['oauth_token']);
        $this->syncItem->syncData($xml);
    }

    protected function syncTransactionContact($token)
    {
        $xml = $this->customerSync->getTransactionContactXml();
        $this->customerSync->getClient()->getSignature()->setOauthSecret($token['oauth_token_secret']);
        $this->customerSync->getClient()->getSignature()->setOauthToken($token['oauth_token']);
        $this->customerSync->syncData($xml);
    }
}
