<?php
namespace Magenest\Xero\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditCollection;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Directory\Model\CountryFactory;

class Helper
{
    protected $_scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

    protected $_scopeId = 0;

    protected $_savedIds = [];

    protected $_xmlLog;

    protected $_coreConfig;

    protected $_messageManager;

    protected $_configWriter;

    protected $_countryFactory;

    protected $_productFactory;

    public function __construct(
        XmlLogFactory $xmlLogFactory,
        CoreConfig $coreConfig,
        ManagerInterface $manager,
        WriterInterface $writer,
        CountryFactory $countryFactory,
        ProductFactory $productFactory
    ){
        $this->_xmlLog = $xmlLogFactory;
        $this->_coreConfig = $coreConfig;
        $this->_messageManager = $manager;
        $this->_configWriter = $writer;
        $this->_countryFactory = $countryFactory;
        $this->_productFactory = $productFactory;
    }

    public function setScope($scope)
    {
        $this->_scope = $scope;
    }

    public function getScope()
    {
        return $this->_scope;
    }

    public function setScopeId($scopeId)
    {
        $this->_scopeId = $scopeId;
    }

    public function getScopeId()
    {
        return $this->_scopeId;
    }

    public function addSavedId($key, $id, $type)
    {
            $this->_savedIds[$type][$this->_scope][$this->_scopeId][$key] = $id;
    }

    public function getSavedId($key, $type)
    {
        if ($type == "BankTransaction") {
            $key = "NONE";
        }
        return isset($this->_savedIds[$type][$this->_scope][$this->_scopeId][$key]) ?
                    $this->_savedIds[$type][$this->_scope][$this->_scopeId][$key] : null;
    }

    public function getIdInCollectionByMagentoId($id, $type)
    {
        $collection = $this->_xmlLog->create()->getCollection()
            ->addFieldToFilter('magento_id', $id)
            ->addFieldToFilter('type', $type)
            ->setOrder('id', 'DESC');
        return $collection->getFirstItem()->getId();
    }

    public function isXeroConnected($id)
    {
        if (!$this->_coreConfig->getConfigValueByScope(
            Config::XML_PATH_XERO_IS_CONNECTED,
            ScopeInterface::SCOPE_WEBSITES ,
            $id
        )) {
            return $this->handleConnectionError();
        }
        return true;
    }

    protected function handleConnectionError()
    {
        $this->_messageManager->addErrorMessage('Please connect the integration to your Xero account first!');
        return false;
    }

    /**
     * @param $id
     * @param $factory
     * @param $idKey
     * @return boolean
     */
    public function isXeroConnectedByIds($id, $factory, $idKey)
    {
        if (!$this->isMultipleWebsiteEnable()) {
            if (!$this->isDefaultXeroAccountConnected()) {
                return $this->handleConnectionError();
            }
            return true;
        }

        if (!is_array($id)) {
            $id = array($id);
        }
        $collection = $factory->create();
        $collection
            ->addAttributeToSelect('*')
            ->addFieldToFilter($idKey, ['IN' => $id]);
        $websiteIds = [];
        if ($collection instanceof ProductCollection ) {
            $websiteIds = $this->getProductWebsiteIds($collection);
        } else if ($collection instanceof CustomerCollection) {
            $websiteIds = $this->getCustomerWebsiteIds($collection);
        } else if ($collection instanceof OrderCollection
            || $collection instanceof InvoiceCollection
            || $collection instanceof CreditCollection
        ) {
            $websiteIds = $this->getOrderWebsiteIds($collection);
        }

        $websiteIds = array_unique($websiteIds);
        foreach ($websiteIds as $id) {
            if (!$this->isXeroConnected($id)) {
                return false;
            }
        }
        return true;
    }

    protected function getProductWebsiteIds($collection) {
        $websiteIds = [];
        foreach($collection as $model) {
            $websiteIds = array_merge_recursive($model->getWebsiteIds(), $websiteIds);
        }
        return $websiteIds;
    }

    protected function getCustomerWebsiteIds($collection) {
        $websiteIds = [];
        foreach ($collection as $model) {
            $websiteIds[] = $model->getWebsiteId();
        }
        return $websiteIds;
    }

    protected function getOrderWebsiteIds($collection) {
        $websiteIds = [];
        foreach ($collection as $model) {
            $websiteIds[] = $model->getStore()->getWebsiteId();
        }
        return $websiteIds;
    }

    public function isMultipleWebsiteEnable()
    {
        return $this->_coreConfig->getConfigValueByScope(
            Config::XML_PATH_XERO_MULTIPLE_ENABLED,
            'default',
            0);
    }

    protected function isDefaultXeroAccountConnected()
    {
        return $this->_coreConfig->getConfigValueByScope(
            Config::XML_PATH_XERO_IS_CONNECTED,
            'default',
            0);
    }

    public function getConfig($path)
    {
        return $this->_coreConfig->getConfigValueByScope(
            $path,
            $this->_scope,
            $this->_scopeId
        );
    }

    public function getDiscount($item)
    {
        if ($item->getDiscountPercent() > 0) {
            return $item->getDiscountPercent() ;
        } elseif ($item->getDiscountAmount() > 0) {
            $discountPercent = $item->getDiscountAmount() / $item->getRowTotal() * 100;
            return $discountPercent;
        }
        return 0.0;
    }

    public function saveConfig($path, $value)
    {
        $this->_configWriter->save($path, $value, $this->_scope, $this->_scopeId);
    }

    public function getCountryName($countryCode)
    {
        $country = $this->_countryFactory->create()->load($countryCode);
        return $country->getName();
    }

    public function createProduct($item)
    {
        $product = $this->_productFactory->create();
        $product->setData([
            'price' => $item->getPrice(),
            'sku' => $item->getSku(),
            'type_id' => $item->getProductType(),
            'name' => $item->getName(),
            'cost' => $item->getCost()
        ]);
        return $product;
    }
}