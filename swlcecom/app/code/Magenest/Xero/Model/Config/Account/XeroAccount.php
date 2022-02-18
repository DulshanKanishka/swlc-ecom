<?php
namespace Magenest\Xero\Model\Config\Account;

use Magenest\Xero\Model\Parser;
use Magenest\Xero\Model\XeroClient;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config;
use Magento\Framework\App\RequestInterface;
use Magenest\Xero\Model\Helper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class XeroAccount
 * @package Magenest\Xero\Model\Config\Account
 */
abstract class XeroAccount extends \Magento\Framework\DataObject
    implements \Magento\Framework\Option\ArrayInterface, \Magento\Eav\Model\Entity\Attribute\Source\SourceInterface
{
    const CACHE_ID = 'XEROACCOUNTS';

    protected $xeroClient;

    protected static $accounts = null;

    protected $types = [];

    protected $_options = [];

    protected $useCode = false;


    protected $_attribute;

    protected $_cache;

    protected $_request;

    protected $_helper;

    protected $_cacheId;

    protected $_storeManager;

    public function __construct(
        XeroClient $xeroClient,
        CacheInterface $cache,
        RequestInterface $request,
        Helper $helper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->xeroClient = $xeroClient;
        $this->_cache = $cache;
        $this->_request = $request;
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
        $this->setCacheId();
        $this->prepareOptions();
        parent::__construct($data);
    }


    protected function prepareOptions()
    {
        if ($this->_helper->isMultipleWebsiteEnable()) {
            if ($this->_request->getParam('store')) {
                $websiteId = $this->_storeManager->getStore($this->_request->getParam('store'))->getWebsiteId();
                $this->_helper->setScope('websites');
                $this->_helper->setScopeId($websiteId);
            }
        }
        if ($cached = $this->_cache->load($this->_cacheId)) {
            self::$accounts = unserialize($cached);
        }
        if (is_null(self::$accounts)) {
            try {
                $helper = $this->xeroClient->getSignature();
                $helper->setUri('Accounts');
                $helper->setMethod();
                $helper->setParams();
                $url = $helper->getUri() . '?' . $helper->sign();

                $client = new \Zend_Http_Client($url, [
                    'timeout' => 30,
                    'useragent' => XeroClient::getUserAgent()
                ]);
                $response = $client->request()->getBody();
                $parsedResponse = Parser::parseXML($response);
                if (isset($parsedResponse['Accounts']['Account'])) {
                    self::$accounts = $parsedResponse['Accounts']['Account'];
                } else {
                    self::$accounts = [];
                }
                $this->_cache->save(serialize(self::$accounts), $this->_cacheId, [Config::CACHE_TAG]);
            } catch (\Exception $e) {
                \Magento\Framework\App\ObjectManager::getInstance()->create('Psr\Log\LoggerInterface')->debug('Xero get Accounts error. '.$e->getMessage());
            }
        }
    }

    protected function loadOptions()
    {
        if (!is_array(self::$accounts)) {
            return $this->_options;
        }
        if ($this->isEmpty(self::$accounts)) {
            $this->prepareOptions();
        }
        foreach (self::$accounts as $account) {
            if (isset($account['Type']) && in_array($account['Type'], $this->types)) {
                $value = $this->useCode ? $account['Code'] : $account['AccountID'];
                $name = $account['Name'];
                $code = isset($account['Code']) ? '['.$account['Code'].']' : '';
                $this->_options[$value] = $name.$code;
            }
        }
        return $this->_options;
    }

    public function toOptionArray()
    {
        if (!is_array(self::$accounts)) {
            return $this->_options;
        }
        foreach (self::$accounts as $account) {
            if (isset($account['Type']) && in_array($account['Type'], $this->types)) {
                $value = $this->useCode ? $account['Code'] : $account['AccountID'];
                $name = $account['Name'];
                $code = isset($account['Code']) ? '['.$account['Code'].']' : '';
                $this->_options[$value] = $name.$code;
            }
        }
        return $this->_options;
    }

    /**
     * @param $attribute
     * @return $this
     */
    public function setAttribute($attribute)
    {
        $this->_attribute = $attribute;
        return $this;
    }

    /**----------
     * @return mixed
     */
    public function getAttribute()
    {
        return $this->_attribute;
    }

    public function getAllOptions()
    {
        $options = [];
        foreach ($this->toOptionArray() as $key => $value) {
            $option['label'] = __($value);
            $option['value'] = $key;
            $options[] = $option;
        }
        return $options;
    }

    /**
     * Get a text for option value
     *
     * @param string|integer $value
     * @return string|bool
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }

    protected function setCacheId()
    {
        if ($this->_helper->isMultipleWebsiteEnable()) {
            if ($this->_request->getParam('store')) {
                $scopeId = $this->_storeManager->getStore($this->_request->getParam('store'))->getWebsiteId();
            } else {
                $scopeId = $this->_request->getParam('website');
            }
            $this->_cacheId = self::CACHE_ID;
            if ($scopeId) {
                $this->_helper->setScopeId($scopeId);
                $this->_helper->setScope(ScopeInterface::SCOPE_WEBSITES);
                $this->_cacheId .= "_" . $scopeId;
            }
        }
    }
}
