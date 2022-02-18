<?php

namespace NeoSolax\QuickBooksOnline\Model\Synchronization;

use Magenest\QuickBooksOnline\Model\Category;
use Magenest\QuickBooksOnline\Model\Client;
use Magenest\QuickBooksOnline\Model\Log;
use Magenest\QuickBooksOnline\Model\Synchronization\Account;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Psr\Log\LoggerInterface;

class Item extends \Magenest\QuickBooksOnline\Model\Synchronization\Item
{
    public function __construct(
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        Client $client,
        Log $log,
        Category $category,
        LoggerInterface $logger,
        ProductFactory $productFactory,
        Account $account,
        Registry $registry,
        Context $context,
        StockRegistryInterface $stockInterface,
        ProductMetadataInterface $productMetadata,
        ModuleManager $moduleManager,
        TimezoneInterface $timezone,
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriorityInterface,
        SourceItemInterfaceFactory $sourceItemFactory
    )
    {
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->getSourcesAssignedToStockOrderedByPriorityInterface = $getSourcesAssignedToStockOrderedByPriorityInterface;
        parent::__construct($client, $log, $category, $logger, $productFactory, $account, $registry, $context, $stockInterface, $productMetadata, $moduleManager, $timezone);
    }

    public function getProductInventory($StartPosition)
    {
        $query = "select * from Item where  Type = 'Inventory' STARTPOSITION $StartPosition MAXRESULTS 1000";

        $path = 'query?query=' . rawurlencode($query);
        $responses = $this->sendRequest(\Zend_Http_Client::GET, $path);
        $result = $responses['QueryResponse'];

        return $result;
    }

    public function getProductNonInventory($StartPosition)
    {
        $query = "select * from Item where  Type = 'Non-inventory' STARTPOSITION $StartPosition MAXRESULTS 1000";

        $path = 'query?query=' . rawurlencode($query);
        $responses = $this->sendRequest(\Zend_Http_Client::GET, $path);
        $result = $responses['QueryResponse'];

        return $result;
    }

    public function UpdateInventoryItemStock()
    {
        $QBItemCount = $this->getCountProduct();
        $num = round($QBItemCount / 1000);
        for ($x = 0; $x <= $num; $x++) {
            $StartPosition = $x * 1000 + 1;
            $prodCollection = $this->getProductInventory($StartPosition);
            if ($prodCollection) {
                foreach ($prodCollection['Item'] as $product) {
                    $sku = $product['Name'];
                    $productModel = $this->_productFactory->create();
                    $productId = $productModel->getIdBySku($sku);
//                    $model = $productModel->load($productId);
                    $QBQty = $product['QtyOnHand'];
                    $currentQty = $this->stockInterface->getStockItem($productId)->getQty();
                    if ($QBQty) {
                        if ($currentQty != $QBQty) {
                            $stockId = $this->stockInterface->getStockItem($productId)->getStockId();
                            $sources = $this->getSourcesAssignedToStockOrderedByPriorityInterface->execute($stockId);
                            foreach ($sources as $source) {
                                $sourceCode = $source->getSourceCode();
                            }
                            $sourceItem = $this->sourceItemFactory->create();
                            $sourceItem->setSourceCode($sourceCode);
                            $sourceItem->setSku($sku);
                            $sourceItem->setQuantity($QBQty);
                            $sourceItem->setStatus(1);
                            $this->sourceItemsSaveInterface->execute([$sourceItem]);
                        }
                    }
                }
            }
        }
    }

    public function UpdateNonInventoryItemStock()
    {
        $QBItemCount = $this->getCountProduct();
        $num = round($QBItemCount / 1000);
        for ($x = 0; $x <= $num; $x++) {
            $StartPosition = $x * 1000 + 1;
            $prodCollection = $this->getProductNonInventory($StartPosition);
            if ($prodCollection) {
                foreach ($prodCollection['Item'] as $product) {
                    $sku = $product['Name'];
                    $productModel = $this->_productFactory->create();
                    $productId = $productModel->getIdBySku($sku);
//                    $model = $productModel->load($productId);
                    $QBQty = $product['QtyOnHand'];
                    if ($QBQty) {
                        $currentQty = $this->stockInterface->getStockItem($productId)->getQty();
                        if ($currentQty != $QBQty) {
                            $stockId = $this->stockInterface->getStockItem($productId)->getStockId();
                            $sources = $this->getSourcesAssignedToStockOrderedByPriorityInterface->execute($stockId);
                            foreach ($sources as $source) {
                                $sourceCode = $source->getSourceCode();
                            }
                            $sourceItem = $this->sourceItemFactory->create();
                            $sourceItem->setSourceCode($sourceCode);
                            $sourceItem->setSku($sku);
                            if ($QBQty == 0) {
                                $sourceItem->setQuantity($QBQty);
                                $sourceItem->setStatus(0);
                            } else {
                                $sourceItem->setQuantity($QBQty);
                                $sourceItem->setStatus(1);
                            }
                            $this->sourceItemsSaveInterface->execute([$sourceItem]);
                        }
                    }
                }
            }
        }
    }

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
            if ($config->isStripHTML() == 1) {
                $description = mb_substr(Strip_tags($model->getShortDescription()), 0, 4000);
            } else {
                $description = mb_substr($model->getShortDescription(), 0, 4000);
            }
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
                'InvStartDate' => (new \DateTimeZone($this->timezone->getConfigTimezone()))->getOffset(new \DateTime()) == 0 ? $model->getCreatedAt() :
                    $this->timezone->date($model->getCreatedAt())->format('Y-m-d'),
                'TrackQtyOnHand' => true,
            ];
            $params = array_replace_recursive($params, $paramSub);
//            if (!strstr($name, 'DeletedItem')) {
//                $params['QtyOnHand'] = $this->getSalableQty($model->getSku(), $params['QtyOnHand']);
//            }
        }
        $this->setParameter($params);

        return $this;
    }

    public function getQboIds($model)
    {
        $qboId = $model->getQboId();
        if ($qboId) {
            $companyId = (string)$this->companyId;
            return (int)substr($qboId, strlen($companyId));
        } else {
            return 0;
        }
    }
}
