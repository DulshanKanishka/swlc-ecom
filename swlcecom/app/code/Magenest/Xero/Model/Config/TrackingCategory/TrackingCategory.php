<?php
namespace Magenest\Xero\Model\Config\TrackingCategory;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\Parser;
use Magenest\Xero\Model\XeroClient;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
/**
 * Class XeroAccount
 * @package Magenest\Xero\Model\Config\Account
 */
class TrackingCategory extends \Magento\Framework\DataObject
    implements \Magento\Framework\Option\ArrayInterface, \Magento\Eav\Model\Entity\Attribute\Source\SourceInterface
{
    const CACHE_ID = 'XERO_TRACKINGCATEGORY';
    protected $xeroClient;

    protected static $categories = null;

    protected $types = [];

    protected $_options = [null => '----------Please Select----------'];

    protected $_attribute;

    protected $_cache;

    protected $_helper;

    protected $_request;

     protected $_storeManager;

    public function __construct(
        XeroClient $xeroClient,
        CacheInterface $cache,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        Helper $helper,
        array $data = []
    )
    {
        $this->xeroClient = $xeroClient;
        $this->_cache = $cache;
        $this->_helper = $helper;
        $this->_request = $request;
        $this->_storeManager = $storeManager;
        $this->prepareOptions();
        parent::__construct($data);
    }


    protected function prepareOptions()
    {
        $cacheId = self::CACHE_ID;
        if ($this->_helper->isMultipleWebsiteEnable()) {
            if ($this->_request->getParam('store')) {
                $scopeId = $this->_storeManager->getStore($this->_request->getParam('store'))->getWebsiteId();
            } else {
                $scopeId = $this->_request->getParam('website');
            }
            if ($scopeId) {
                $this->_helper->setScopeId($scopeId);
                $this->_helper->setScope(ScopeInterface::SCOPE_WEBSITES);
                $cacheId .= "_".$scopeId;
            }
        }

        if ($cached = $this->_cache->load($cacheId)) {
            self::$categories = unserialize($cached);
        }
        if (is_null(self::$categories)) {
            try {
                $helper = $this->xeroClient->getSignature();
                $helper->setUri('TrackingCategories');
                $helper->setMethod();
                $helper->setParams();
                $url = $helper->getUri() . '?' . $helper->sign();

                $client = new \Zend_Http_Client($url, [
                    'timeout' => 30,
                    'useragent' => XeroClient::getUserAgent()
                ]);
                $response = $client->request()->getBody();
                $parsedResponse = Parser::parseXML($response);
                if (isset($parsedResponse['TrackingCategories']['TrackingCategory'])) {
                    self::$categories = $parsedResponse['TrackingCategories']['TrackingCategory'];
                } else {
                    self::$categories = [];
                }
                $this->_cache->save(serialize(self::$categories), $cacheId, [Config::CACHE_TAG]);
            } catch (\Exception $e) {
                \Magento\Framework\App\ObjectManager::getInstance()->create('Psr\Log\LoggerInterface')->debug('Xero get Accounts error. '.$e->getMessage());
            }
        }
    }

    public function toOptionArray()
    {
        if (!is_array(self::$categories)) {
            return $this->_options;
        }
        $categories = self::$categories;
        $this->_options = [];
        if (!isset($categories[0])) {
            $this->processCategory($categories);
        } else {
            foreach ($categories as $category) {
                $this->processCategory($category);
            }
        }
        return $this->_options;
    }

    protected function processCategory($category)
    {
        if (isset($category['Name']) && $category['Status'] == 'ACTIVE' && isset($category['Options']['Option'])) {
            $this->_options[] = '------ Category: ' . $category['Name'] . '------ (Do not choose this)';
            $categoryOptions = $category['Options']['Option'];
            if (!isset($categoryOptions[0])) {
                $this->processOption($category['Name'], $categoryOptions);
            } else {
                foreach ($categoryOptions as $categoryOption) {
                    $this->processOption($category['Name'], $categoryOption);
                }
            }
        }
    }

    protected function processOption($categoryName, $option)
    {
        if (isset($option['Name']) && $option['Status'] == 'ACTIVE') {
            $this->_options[$categoryName . '/' . $option['Name']] = $option['Name'];
        }
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

    /**
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
}
