<?php
namespace Magenest\Xero\Model\Synchronization;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\LogFactory;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Model\XeroClient;
use Magento\Catalog\Model\ProductFactory;
use Magenest\Xero\Model\RequestLogFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magenest\Xero\Model\QueueFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;


/**
 * Class Item
 * @package Magenest\Xero\Model\Synchronization
 */
class Item extends Synchronization
{
    const SYNC_STOCK_ENABLE = 'magenest_xero_config/xero_item/sync_qty';
    /**
     * @var string
     */
    protected $type = 'Item';

    protected $syncType = 'Item';

    protected $syncIdKey = 'Code';

    protected $syncTypeKey = 'Item';

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magenest\Xero\Model\Synchronization\BankTransaction
     */
    protected $syncBankTransaction;

    /**
     * @var string
     */
    protected $bankTransactionXml = '';

    /**
     * @var string
     */
    protected $cogsAccountId = '';

    /**
     * @var string
     */
    protected $inventoryAccountId = '';

    /**
     * @var string
     */
    protected $saleAccountId = '';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    protected $syncStock = false;

    protected $storeManager;

    /**
     * Item constructor.
     * @param XeroClient $xeroClient
     * @param LogFactory $logFactory
     * @param BankTransaction $syncBankTransaction
     * @param Account $accountConfig
     * @param ProductFactory $productFactory
     * @param RequestLogFactory $requestLogFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param QueueFactory $queueFactory
     * @param CollectionFactory $collectionFactory
     * @param Helper $helper
     * @param StoreManagerInterface $storeManager
     * @throws \Exception
     */
    public function __construct(
        XeroClient $xeroClient,
        LogFactory $logFactory,
        BankTransaction $syncBankTransaction,
        Account $accountConfig,
        ProductFactory $productFactory,
        RequestLogFactory $requestLogFactory,
        ScopeConfigInterface $scopeConfig,
        QueueFactory $queueFactory,
        CollectionFactory $collectionFactory,
        Helper $helper,
        StoreManagerInterface $storeManager
    ) {
        $this->productFactory = $productFactory;
        $this->syncBankTransaction = $syncBankTransaction;
        $this->cogsAccountId = $accountConfig->getAccountId($accountConfig::COGS_ACC_TYPE);
        $this->inventoryAccountId = $accountConfig->getAccountId($accountConfig::INVENTORY_ACC_TYPE);
        $this->saleAccountId = $accountConfig->getAccountId($accountConfig::SALE_ACC_TYPE);
        $this->scopeConfig = $scopeConfig;
        $this->syncStock = $scopeConfig->getValue(self::SYNC_STOCK_ENABLE);
        $this->limit = 500;
        $this->collectionFactory = $collectionFactory;
        $this->id = "entity_id";
        $this->storeManager = $storeManager;
        parent::__construct($xeroClient, $logFactory, $requestLogFactory, $queueFactory, $helper);
    }


    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function addRecord($product)
    {
        if (!$product->getId()) {
            return '';
        }
        $name = $product->getName();
        if (strlen($name) >= 50) {
            $name = substr($name, 0, 49);
        }
        $description = strip_tags($product->getDescription());
        if (strlen($description) >= 4000) {
            $description = substr($description, 0, 3999);
        }
        $price = $product->getPrice() ? $product->getPrice() : 0;
        $inventoryAccountId = $this->inventoryAccountId;
        if ($this->helper->isMultipleWebsiteEnable()) {
            $cogsAccountCode = $this->helper->getConfig(Account::ACC_PATH.'cog_id');
            $saleAccountCode = $this->helper->getConfig(Account::ACC_PATH.'sale_id');
            $inventoryAccountId = $this->helper->getConfig(Account::ACC_PATH.'inventory_id');

            foreach($product->getStoreIds() as $storeId) {
                $store = $this->storeManager->getStore($storeId);
                if ($store->getWebsiteId() == $this->helper->getScopeId()){
                    $cogsAccountCode = $this->getAttributeByWebsite($product, 'cogs_id', $storeId) ? : $cogsAccountCode;
                    $saleAccountCode = $this->getAttributeByWebsite($product, 'sale_id', $storeId) ? : $saleAccountCode;
                    $price = $this->getAttributeByWebsite($product, 'price', $storeId) ? : $price;
                    $name = $this->getAttributeByWebsite($product, 'name', $storeId) ? : $name;
                    if (strlen($name) >= 50) {
                        $name = substr($name, 0, 49);
                    }
                }
            }
        } else {
            $cogsAccountCode = $product->getData('cogs_id');
            $saleAccountCode = $product->getData('sale_id');

            $cogsAccountCode = $cogsAccountCode ? : $this->cogsAccountId;
            $saleAccountCode = $saleAccountCode ? : $this->saleAccountId;
        }
        $xml = '<Item>';
        $xml .= '<Code>' . $product->getSku() . '</Code>';
        $xml .= '<Name>'. $name .'</Name>';
        $xml .= '<Description>'. $description .'</Description>';
        if ($cogsAccountCode) {
            if ($this->syncStock && $inventoryAccountId) {
                $xml .= '<InventoryAssetAccountCode>' . $inventoryAccountId . '</InventoryAssetAccountCode>';
                $xml .= '<PurchaseDetails>';
                $xml .= '<COGSAccountCode>' . $cogsAccountCode . '</COGSAccountCode>';
            } else {
                $xml .= '<PurchaseDetails><AccountCode>'. $cogsAccountCode .'</AccountCode>';
            }

            $costPrice = $product->getCost() ? $product->getCost() : 0;
            $xml .= '<UnitPrice>'.$costPrice.'</UnitPrice>';
            $xml .= '</PurchaseDetails>';
        }
        $xml .= '<SalesDetails>';
        $xml .= '<UnitPrice>' . $price . '</UnitPrice>';

        if ($saleAccountCode) {
            $xml .= '<AccountCode>' . $saleAccountCode .'</AccountCode>';
        }
        $xml .='</SalesDetails>';
        $xml .= '</Item>';

        return $xml;
    }

//    /**
//     * @return string
//     */
//    public function getTaxItemXml()
//    {
//        $xml = '<Item>';
//        $xml .= '<Code>tax</Code>';
//        $xml .= '<Name>TAX</Name>';
//        $xml .= '</Item>';
//
//        return $xml;
//    }

    /**
     * @return string
     */
    public function getShippingItemXml()
    {
        $xml = '<Item>';
        $xml .= '<Code>shipping</Code>';
        $xml .= '<Name>SHIPPING COST</Name>';
        $xml .= '</Item>';

        return $xml;
    }

    /**
     * {@inheritdoc}
     */
    protected function _additional($product)
    {
        return $this->syncBankTransaction->addRecord($product);
    }

    /**
     * {@inheritdoc}
     */
    protected function _additionalSync($additionalXml)
    {
        $xml = $this->syncBankTransaction->addOtherTags($additionalXml);
        $this->syncBankTransaction->syncData($xml);
    }

    protected function getAttributeByWebsite($product, $code, $storeId)
    {
        return $product->getResource()->getAttributeRawValue($product->getId(), $code, $storeId);
    }
}
