<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Model\Synchronization;

use Magenest\QuickBooksOnline\Model\Client;
use Magenest\QuickBooksOnline\Model\Config\Source\NameRule;
use Magenest\QuickBooksOnline\Model\Log;
use Magenest\QuickBooksOnline\Model\Synchronization;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\App\Action\Context;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use u2flib_server\Error;

/**
 * Class Item
 * @package Magenest\QuickBooksOnline\Model\Sync
 * @method Product getModel()
 */
class Item extends Synchronization
{
    /**
     * @var Category
     */
    protected $_category;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var Account
     */
    protected $_account;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var StockRegistryInterface
     */
    protected $stockInterface;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * Item constructor.
     *
     * @param Client $client
     * @param Log $log
     * @param \Magenest\QuickBooksOnline\Model\Category $category
     * @param \Psr\Log\LoggerInterface $logger
     * @param ProductFactory $productFactory
     * @param Account $account
     * @param Registry $registry
     * @param Context $context
     * @param StockRegistryInterface $stockInterface
     * @param ProductMetadataInterface $productMetadata
     * @param ModuleManager $moduleManager
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Client $client,
        Log $log,
        \Magenest\QuickBooksOnline\Model\Category $category,
        \Psr\Log\LoggerInterface $logger,
        ProductFactory $productFactory,
        Account $account,
        Registry $registry,
        Context $context,
        StockRegistryInterface $stockInterface,
        ProductMetadataInterface $productMetadata,
        ModuleManager $moduleManager,
        TimezoneInterface $timezone

    )
    {
        parent::__construct($client, $log, $context);
        $this->_productFactory = $productFactory;
        $this->_account = $account;
        $this->_category = $category;
        $this->type = 'item';
        $this->logger = $logger;
        $this->registry = $registry;
        $this->stockInterface = $stockInterface;
        $this->productMetadata = $productMetadata;
        $this->moduleManager = $moduleManager;
        $this->timezone = $timezone;
    }

    /**
     * @param $productId
     * @param bool $update
     * @param null $sku
     * @param null $orderedQty
     *
     * @return mixed
     * @throws LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function sync($productId, $update = false, $sku = null, $orderedQty = null)
    {
        $model = $this->_productFactory->create()->load($productId);
        $type = (string)$model->getTypeId();
        if ($type == "configurable") {
            if (empty($sku)) {
                $parentId = $productId;
                $arrayId = [];
                $id = $this->sendItems($parentId, $update);
                $arrId = [];
                $arrId[] = $id;
                $usedProducts = $model->getTypeInstance()->getUsedProducts($model);
                foreach ($usedProducts as $child) {
                    $childId[] = $child->getId();
                    $arrayId = array_merge($childId);
                }
                foreach ($arrayId as $productId) {
                    $id = $this->sendItems($productId, $update);
                    if ($id) $arrId[] = $id;
                }
                $this->registry->unregister('arr_id' . $productId);
                $this->registry->register('arr_id' . $productId, $arrId);
            } else {
                $productId = $model->getIdBySku($sku);
                $id = $this->sendItems($productId, $update);
            }
        } else {
            $id = $this->sendItems($productId, $update, null, $orderedQty);
        }

        return $id;
    }

    /**
     * @param $sku
     * @param bool $update
     * @param null $orderedQty
     *
     * @return mixed
     * @throws LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function syncBySku($sku, $update = false, $orderedQty = null)
    {
        $productModel = $this->_productFactory->create();
        $productId = $productModel->getIdBySku($sku);
        $model = $productModel->load($productModel->getIdBySku($sku));
        $type = (string)$model->getTypeId();
        if ($type == "configurable") {
            if (empty($sku)) {
                $parentId = $productId;
                $arrayId = [];
                $id = $this->sendItems($parentId, $update, null, null, $sku);
                $arrId = [];
                $arrId[] = $id;
                $usedProducts = $model->getTypeInstance()->getUsedProducts($model);
                foreach ($usedProducts as $child) {
                    $childId[] = $child->getId();
                    $arrayId = array_merge($childId);
                }
                foreach ($arrayId as $productId) {
                    $id = $this->sendItems($productId, $update, null, null, $sku);
                    if ($id) $arrId[] = $id;
                }
                $this->registry->unregister('arr_id' . $productId);
                $this->registry->register('arr_id' . $productId, $arrId);
            } else {
                $productId = $model->getIdBySku($sku);
                $id = $this->sendItems($productId, $update);
            }
        } else {
            $id = $this->sendItems($productId, $update, null, $orderedQty, $sku);
        }

        return $id;
    }

    /**
     * @param $id
     * @param bool $update
     * @param null $qty
     * @param null $orderedQty
     * @param null $sku
     *
     * @return mixed
     * @throws LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function sendItems($id, $update = false, $qty = null, $orderedQty = null, $sku = null)
    {
        /**
         * @var \Magenest\QuickBooksOnline\Model\Config $config
         */
        $config = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Config');
        /** @var \Magento\Framework\Registry $registryObject */
        $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');

        if ($registryObject->registry('check_to_syn' . $id) != true || empty($id)) {
            $productFactory = $this->_productFactory->create();
            if (!empty($sku) && $id == null)
                $model = $productFactory->load($productFactory->getIdBySku($sku));
            else
                $model = $productFactory->load($id);
            $this->setModel($model);

            $qboId = $this->getQboId($model);

            if ($model->getName() == 'DeletedItem') {
                if (empty($sku))
                    $sku = time();
                $model->setName('DeletedItem-' . $sku);
                $model->setSku('DeletedItem-' . $sku);
                $model->setShortDescription('DeletedItem-' . $sku);
                $model->setCreatedAt('1970-01-01');
                $model->setPrice(0);
                /* if syncing deleted product, check if deleteditem already exits on QBO, match params with deleteditem on QBO */
                $deletedProduct = $this->checkDeletedItem('DeletedItem-' . $sku);
                if (isset($deletedProduct['Id'])) {
//                    $model->setCreatedAt($deletedProduct['InvStartDate']);
                    $qboId = $deletedProduct['Id'];
                }
            }

            if (!empty($deletedProduct['Id']) && !empty($qboId) && (strpos($model->getName(), 'DeletedItem') === 0)) {
                $product = $deletedProduct;
            } else if (!empty($qboId)) {
//            $needSave = false;
                $product = $this->checkProductByQboId($qboId);
                if (empty($product['Id']) && $model->getName() != 'DeletedItem') {
                    $model->unsetData('qbo_id');
                    if (!empty($model->getId())) {
                        $model->getResource()->saveAttribute($model, 'qbo_id');
                    }
                    $name = $model->getName();
                    $sku = $model->getSku();
                    $qboName = $this->setName($name, $sku);
                    $product = $this->checkProduct($qboName);

                    if (isset($product['Id']) && $config->getNewItemName() != 2) {
                        $registryObject->unregister('skip_log');
                        $this->addLog($id, null,
                            'A product with the same name already exists on QuickBooks Online.
                        Please correct the product name before syncing, or allow Magento to override QBO data from QuickBooks Online > Configuration > Synchronization Settings > Products.');

                        return null;
                    }
                }
            } else {
//            $needSave = true;
                $name = $model->getName();
                $sku = $model->getSku();
                $qboName = $this->setName($name, $sku);
                $product = $this->checkProduct($qboName);
                /**
                 * For new product with duplicate name, throw error
                 */
                if (isset($product['Id']) && $config->getNewItemName() != 2) {
                    $registryObject->unregister('skip_log');
                    $this->addLog($id, null,
                        'A product with the same name already exists on QuickBooks Online.
                        Please correct the product name before syncing, or allow Magento to override QBO data from QuickBooks Online > Configuration > Synchronization Settings > Products.');

                    return null;
                }
            }

            if (isset($product['Id']) && !$update) {
                return $product['Id'];
            }

            $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');

            $this->prepareParams();
            $params = $this->getParameter();

            //remove current date on QB in case of UK timezone error
            if (isset($product['InvStartDate']) && strtotime($product['InvStartDate']) > strtotime(date('Y-m-d')))
                unset($product['InvStartDate']);

            $params = array_replace_recursive($params, $product);
            if (!empty($qty) || $qty === 0 || $qty === 0.0) {
                $params['QtyOnHand'] = $qty;
            }

            /*update qty on hand in case of order create, for Magento 2.3 MSI*/
            $magentoVer = $this->productMetadata->getVersion();
            $MSIEnabled = $this->moduleManager->isEnabled('Magento_InventorySalesApi');

//            if (version_compare($magentoVer, '2.3', '>=') && $MSIEnabled && !empty($orderedQty)) {
//                $params['QtyOnHand'] -= $orderedQty;
//            }

            try {
                $response = $this->sendRequest(\Zend_Http_Client::POST, 'item?minorversion=6', $params);
                /**
                 * For new product with duplicate name, override data on QBO with product data on Magento
                 */
                if ($config->getNewItemName() == 2 && empty($qboId) && $model->getTypeId() !== 'configurable') {
                    $today = strtotime(date('Y-m-d'));
                    if (isset($response['Item']['InvStartDate'])) {
                        $invStartDate = strtotime($response['Item']['InvStartDate']);
                        if ($today > $invStartDate && ($response['Item']['Name'] != 'DeletedItem')) {
                            $params['Id'] = $response['Item']['Id'];
                            $params['SyncToken'] = $response['Item']['SyncToken'];
                            $this->sendRequest(\Zend_Http_Client::POST, 'item?minorversion=6', $params);
                        }
                    }
                }
                if (isset($response['Item']['Id'])) {
                    $qboId = (string)$response['Item']['Id'];
                    $qboIdTemp = $response['Item']['Id'];
                    $this->addLog($id, $qboId);
                }

                /**
                 * save qbo_id attribute in product
                 */
//            if ($needSave === true){
                $companyId = (string)$config->getCompanyId();
                $qboId = $companyId . $qboId;
                $model->setQboId($qboId);

                /*Avoid saving qbo_id for deleted items */
                if (!empty($model->getId())) {
                    $model->getResource()->saveAttribute($model, 'qbo_id');
                }
//            }
                /**
                 * registry variable used in Magenest\QuickBooksOnline\Observer\Item\Update
                 */
                if (!empty($id)) {
                    $registryObject->unregister('check_to_syn' . $id);
                    $registryObject->register('check_to_syn' . $id, true);
                }

                $this->parameter = [];

                return isset($qboIdTemp) ? $qboIdTemp : null;
            } catch (LocalizedException $e) {
                $this->addLog($id, null, $e->getMessage());
            }
            $this->parameter = [];
        } else $registryObject->unregister('check_to_syn' . $id);
    }

    /**
     * @param $name
     * @param $sku
     *
     * @return string
     */
    public function setName($name, $sku)
    {
        /** @var \Magenest\QuickBooksOnline\Model\Config $config */
        $config = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Config');
        $nameRule = $config->getNameRule();
        $charCount = $config->getCharacterCount();
        switch ($nameRule) {
            case NameRule::USE_SKU:
            {
                $name = mb_substr($sku, 0, $charCount);
                break;
            }
            case NameRule::USE_BOTH:
            {
                $name = trim($sku . '-' . $name);
                $name = mb_substr($name, 0, $charCount);
                break;
            }
            case NameRule::USE_NAME:
            default:
            {
                $name = mb_substr($name, 0, $charCount);
                break;
            }
        }

        return $name;
    }

    /**
     * Set Model
     *
     * @param \Magento\Catalog\Model\Product $model
     *
     * @return $this
     */
    public function setModel($model)
    {
        if (empty($model->getId())) {
            $model = $this->getDeletedModel($model);
            $this->_model = $model;

            return $this;
        }
        /** @var \Magenest\QuickBooksOnline\Model\Config $config */
        $config = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Config');
        $charCount = $config->getCharacterCount();

        $sku = $model->getSku();
        foreach ($this->unSupportedChar as $char) {
            $sku = str_replace($char, " ", $sku);
        }
        $sku = trim($sku);
        $sku = mb_substr($sku, 0, 100);
        $model->setSku($sku);

        $name = $model->getName();
        foreach ($this->unSupportedChar as $char) {
            $name = str_replace($char, " ", $name);
        }
        $name = trim($name);
        $name = $this->setName($name, $sku);

        $model->setName($name);

        $isDuplicate = ObjectManager::getInstance()->get(Registry::class)->registry('is_duplicate');
        if ($isDuplicate) {
            $model->unsetData('qbo_id');
            $model->getResource()->saveAttribute($model, 'qbo_id');
            $name = trim($sku . '-' . $name);
            $name = mb_substr($name, 0, $charCount);
            $model->setName($name);
        }
        $this->_model = $model;

        return $this;
    }

    /**
     * @param \Magento\Catalog\Model\Product $model
     *
     * @return mixed
     */
    protected function getDeletedModel($model)
    {
        $model->setName('DeletedItem');

        return $model;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function prepareParams()
    {
        $account = $this->_account;
        $model = $this->getModel();
        $name = $model->getName();
        $qty = $this->stockInterface->getStockItem($model->getId())->getqty();

        /**
         * @var \Magenest\QuickBooksOnline\Model\Config $config
         */
        $config = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Config');

        if ($config->getItemDescription() == 2) {
            $description = $name;
            if (strpos($name, $model->getSku()) === 0) {
                $description = substr($name, strlen($model->getSku()));
                if (empty($description)) {
                    $description = $this->_productFactory->create()->load($model->getId())->getName();
                }
            }
        } else {
            if ($config->isStripHTML() == 1)
                $description = mb_substr(Strip_tags($model->getShortDescription()), 0, 4000);
            else
                $description = mb_substr($model->getShortDescription(), 0, 4000);
        }

        $params = [
            'Name' => $name,
            'Description' => $description,
            'Active' => true,
//            'PurchaseDesc'       => $name,
            'UnitPrice' => !empty($model->getSpecialPrice()) ? $model->getSpecialPrice() : $model->getPrice(),
            'PurchaseCost' => !empty($model->getCost()) ? $model->getCost() : 0,
            'Taxable' => $model->getTaxClassId() == 0 ? false : true,
            'Sku' => $model->getSku(),
            'FullyQualifiedName' => $name,
            'Type' => 'NonInventory',
            'ExpenseAccountRef' => ['value' => $account->sync('expense')],
            'IncomeAccountRef' => ['value' => $account->sync()], /*required to make product salable*/
        ];

        $productType = $model->getTypeId();
        $isTrackQty = $config->getTrackQty();

        if ($productType !== "configurable" && $productType !== "bundle" && $productType !== "grouped" && $isTrackQty == 1) {
            $paramSub = [
                'AssetAccountRef' => ['value' => $account->sync('asset')], /*required for inventory product*/
                'QtyOnHand' => empty($qty) ? 0 : $qty,
                'Type' => 'Inventory',
                'InvStartDate' => (new \DateTimeZone($this->timezone->getConfigTimezone()))->getOffset(new \DateTime) == 0 ? $model->getCreatedAt() :
                    $this->timezone->date($model->getCreatedAt())->format('Y-m-d'),
                'TrackQtyOnHand' => true,
            ];
            $params = array_replace_recursive($params, $paramSub);
            if (!strstr($name, 'DeletedItem')) {
                $params['QtyOnHand'] = $this->getSalableQty($model->getSku(), $params['QtyOnHand']);
            }
        }
        $this->setParameter($params);

        return $this;
    }

    /**
     * Get Salable Qty for Magento 2.3 MSI
     *
     * @param $sku
     * @param $defaultQty
     *
     * @return int
     */
    public function getSalableQty($sku, $defaultQty = 0)
    {
        $magentoVer = $this->productMetadata->getVersion();
        $MSIEnabled = $this->moduleManager->isEnabled('Magento_InventorySalesApi');
        if (version_compare($magentoVer, '2.3', '>=') && $MSIEnabled && ($sku !== 'DeletedItem')) {

            $getProductSalableQty = ObjectManager::getInstance()
                ->create('Magento\InventorySalesApi\Api\GetProductSalableQtyInterface');
            $getStockItemConfiguration = ObjectManager::getInstance()
                ->create('Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface');
            $storeManager = ObjectManager::getInstance()
                ->create('Magento\Store\Model\StoreManagerInterface');
            $websiteCode = $storeManager->getWebsite(1)->getCode();
            $getAssignedStockIdForWebsite = ObjectManager::getInstance()
                ->create('Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite');

            $websiteStockId = $getAssignedStockIdForWebsite->execute($websiteCode); /*get default website's stock Id*/
            $stockItemConfiguration = $getStockItemConfiguration->execute($sku, $websiteStockId);
            $isManageStock = $stockItemConfiguration->isManageStock(); /*check if stock is in use*/
            $stockQty = $isManageStock ? $getProductSalableQty->execute($sku, $websiteStockId) : 0; /*get salable qty of product's valid stock*/
            $qty = $stockQty;

            return $qty;
        } else
            return $defaultQty;
    }

    /**
     * @return int
     * @throws LocalizedException
     * @throws \Zend_Http_Client_Exception
     * @throws \Exception
     */
    public function getAdjustmentItem()
    {
        /**
         * @var \Magenest\QuickBooksOnline\Model\Config $config
         */
        $config = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Config');
        $adjustmentId = $config->getAdjustmentId();
        $adjustmentName = $config->getAdjustmentName();
        if ($adjustmentId) {
            $checkProduct = $this->checkAdjustmentItem($adjustmentId);
            if (!empty($checkProduct)) {
                if ($checkProduct['Name'] == $adjustmentName)
                    return $adjustmentId;
                else {
                    $params = [
                        'Name' => $adjustmentName,
                        'Id' => $adjustmentId,
                        'SyncToken' => $checkProduct['SyncToken'],
                        'Type' => 'Service',
                        'ExpenseAccountRef' => ['value' => $this->_account->sync('expense')],
                        'IncomeAccountRef' => ['value' => $this->_account->sync()],
                    ];

                    if ($config->getCountry() == 'FR')
                        $params['ItemCategoryType'] = 'Service';

                    $this->sendRequest(\Zend_Http_Client::POST, 'item?minorversion=6', $params);

                    return $adjustmentId;
                }
            }
        }

        $params = [
            'Name' => $adjustmentName,
            'Type' => 'Service',
            'Sku' => 'refund-adjustment',
            'ExpenseAccountRef' => ['value' => $this->_account->sync('expense')],
            'IncomeAccountRef' => ['value' => $this->_account->sync()],
        ];

        if ($config->getCountry() == 'FR')
            $params['ItemCategoryType'] = 'Service';

        $checkExisting = $this->checkProduct($adjustmentName);
        if (isset($checkExisting['Id'])) {
            $params = array_replace_recursive($params, $checkExisting);
        }

        $response = $this->sendRequest(\Zend_Http_Client::POST, 'item?minorversion=6', $params);
        if (isset($response['Item']['Id'])) {
            $adjustmentId = $response['Item']['Id'];
            $config->saveAdjustmentId($adjustmentId);

            return $adjustmentId;
        } else throw new \Exception(__('Cannot sync adjustment product to QuickBooks'));
    }

    /**
     * @param $qboId
     *
     * @return array
     * @throws LocalizedException
     */
    public function checkAdjustmentItem($qboId)
    {
        $query = "SELECT Name, SyncToken FROM Item WHERE Id ='{$qboId}' AND Type ='Service'";

        return $this->query($query);
    }

    /**
     * @param $name
     *
     * @return array
     * @throws LocalizedException
     */
    public function checkDeletedItem($name)
    {
        $query = "SELECT Id, SyncToken, InvStartDate FROM Item WHERE Name ='{$name}'";

        return $this->query($query);
    }

    /**
     * @param $qboId
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkProductByQboId($qboId)
    {
        $query = "SELECT Id, SyncToken, InvStartDate, level, ParentRef FROM Item WHERE Id ='{$qboId}'";

        return $this->query($query);
    }

    /**
     * Check item on QBO
     *
     * @param $name
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkProduct($name)
    {
        $query = "SELECT Id, SyncToken, InvStartDate, level, ParentRef FROM Item WHERE name ='{$name}'";

        return $this->query($query);
    }

//    public function checkProductBySku($sku)
//    {
//        $sku   = addslashes($sku);
//        $query = "SELECT Id, SyncToken FROM Item WHERE name ='{$sku}'";
//
//        return $this->query($query);
//    }

    /**
     * Delete Product
     *
     * @param $name
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function delete($name)
    {
        $product = $this->checkProduct($name);
        if (!empty($product)) {
            $params = [
                'Id' => $product['Id'],
                'SyncToken' => $product['SyncToken'],
                'Active' => false,
            ];

            $this->sendRequest(\Zend_Http_Client::POST, 'item', $params);
        }
    }

    /**
     * Query Product
     *
     * @param $params
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function getProduct($params)
    {
        $query = "select * from Item";
        if (isset($params['type']) && $params['type'] == 'time_start') {
            $input = $params['from'];
            $query = "select * from Item where MetaData.LastUpdatedTime >= '$input'";
        }
        if (isset($params['type']) && $params['type'] == 'time_around') {
            $from = $params['from'];
            $to = $params['to'];
            $query = "select * from Item where MetaData.LastUpdatedTime >= '$from' and MetaData.LastUpdatedTime <= '$to'";
        }
        if (isset($params['type']) && $params['type'] == 'name') {
            $input = $params['input'];
            $query = "select * from Item where Name Like '$input'";
        }
        if (isset($params['type']) && $params['type'] == 'id') {
            $input = $params['input'];
            $query = "select * from Item where  Id = '$input'";
        }
        $path = 'query?query=' . rawurlencode($query);
        $responses = $this->sendRequest(\Zend_Http_Client::GET, $path);
        $result = $responses['QueryResponse'];

        return $result;
    }

    /**
     *  count product
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function getCountProduct()
    {
        $query = "select COUNT(*) from Item ";
        $path = 'query?query=' . rawurlencode($query);
        $responses = $this->sendRequest(\Zend_Http_Client::GET, $path);
        $result = $responses['QueryResponse'];

        return $result['totalCount'];
    }

    /**
     * list all produc when creat new
     *
     * @param $start
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function listProduct($start)
    {
        $query = "select * from Item startposition {$start} maxresults 100";
        $path = 'query?query=' . rawurlencode($query);
        $responses = $this->sendRequest(\Zend_Http_Client::GET, $path);
        $result = $responses['QueryResponse'];

        return $result;
    }
}
