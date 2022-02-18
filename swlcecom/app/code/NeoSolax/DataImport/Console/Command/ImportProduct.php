<?php
namespace NeoSolax\DataImport\Console\Command;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ImportProduct extends Command
{
    protected $product;
    protected $stockRegistry;
    private $state;
    private $resource;

    /**
     * @var ProductInterfaceFactory
     */
    private $productFactory;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    private $filesystem;

    public function __construct(
        StoreManagerInterface $storeManager,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        SourceItemInterfaceFactory $sourceItemFactory,
        ProductLinkInterface $productLinks,
        CategoryLinkManagementInterface $categoryLinkManagementInterface,
        CategoryFactory $categoryFactory,
        CollectionFactory $websiteCollectionFactory,
        AttributeFactory $eavAttributeFactory,
        AttributeOptionManagementInterface $attributeOptionManagement,
        Product $products,
        Filesystem $filesystem,
        ResourceConnection $resource,
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        State $state,
        string $name = null
    ) {
        $this->storeManager = $storeManager;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->productLinks = $productLinks;
        $this->categoryLinkManagement = $categoryLinkManagementInterface;
        $this->categoryFactory = $categoryFactory;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->eavAttributeFactory = $eavAttributeFactory;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->product = $products;
        $this->filesystem = $filesystem;
        $this->resource = $resource;
        $this->state = $state;
        $this->stockRegistry = $stockRegistry;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('import:product');
        $this->setDescription('Import Product from old Database to New Database');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

//        $connectionOld = $this->resource->getConnection('old_setup');
        define('DS', DIRECTORY_SEPARATOR);
        $file = fopen('csv/products.csv', 'r', '"'); // set path to the CSV file

        if ($file !== false) {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);

            // add logging capability
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/import-new.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);

            $header = fgetcsv($file); // get data headers and skip 1st row

            // enter the min number of data fields you require that the new product will have (only if you want to standardize the import)
            $required_data_fields = 3;

            while ($row = fgetcsv($file, 3000, ",")) {
                $data_count = count($row);
                if ($data_count < 1) {
                    continue;
                }

                $data = [];
                $data = array_combine($header, $row);

                $sku = $data['sku'];
                if ($data_count < $required_data_fields) {
                    $logger->info("Skipping product sku " . $sku . ", not all required fields are present to create the product.");
                    continue;
                }

                $productType = trim($data['product_type']);
                $name = $data['name'];
                $qty = trim($data['qty']);
                $price = trim($data['price']);

                if (!$price || $price == "") { //Added to make more products available in the live site.
                    $price = '0.00';
                }
                // used for setting the new product data
                $product = $this->productFactory->create();
                if (!$this->product->getIdBySku($sku)) {
                    try {
                        $i = 0;
                        $this->storeManager->setCurrentStore(0);
                        $product->setWebsiteId(0);
                        $product->setStoreId(0); // Default store ID
                        foreach ($data as $value) {
                            if ($header[$i] != 'qty' && $header[$i] != 'total_qty' && $header[$i] != 'out_of_stock_qty') {
                                if (!$product->getData($header[$i])) {
                                    if ($header[$i] == 'additional_attributes') {
                                        $str_arr = explode(",", $data['additional_attributes']);
                                        $j = 0;
                                        foreach ($str_arr as $str_ar) {
                                            $split = explode("=", $str_ar);
                                            $product->setData($split[0], $split[1]);
                                            $j++;
                                        }
                                    } else {
                                        $product->setData($header[$i], $value);
                                    }
                                }
                            }
                            $i++;
                        }

//                        $product->setQty($qty);
                        $product->setTypeId($productType);
                        $product->setStatus(1); // 1 = enabled
                        $product->setAttributeSetId(4);
                        $product->setPriceView(0);
                        $product->setLinksPurchasedSeparately(0);
                        $product->setPrice($price);

                        $connection = $this->resource->getConnection();
                        $tableName = $this->resource->getTableName('eav_attribute_option_value');

                        $attrColorNew = $product->getResource()->getAttribute('color');
                        if ($attrColorNew->usesSource()) {
                            $option_id = $attrColorNew->getSource()->getOptionId($product->getColor());
                            if (!$option_id && $product->getColor()) {
                                $sql = "select * FROM " . $tableName . " where value='" . $product->getColor() . "'";
                                $result = $connection->fetchAll($sql);
                                $option_id = $result[0]['option_id'];
                            }
                            $product->setColor($option_id);
                        }

                        $attrSizeNew = $product->getResource()->getAttribute('size');
                        if ($attrSizeNew->usesSource()) {
                            $option_id = $attrSizeNew->getSource()->getOptionId($product->getSize());
                            if (!$option_id && $product->getSize()) {
                                $sql = "select * FROM " . $tableName . " where value='" . $product->getSize() . "'";
                                $result = $connection->fetchAll($sql);
                                $option_id = $result[0]['option_id'];
                            }
                            $product->setSize($option_id);
                        }

                        $product->setTaxClassId($product->getResource()->getAttribute('tax_class_id')->getSource()->getOptionId($product->getData('tax_class_name')));

                        $array = [1 => 'Not Visible Individually', 2 => 'Catalog', 3 => 'Search', 4 => 'Catalog, Search'];
                        $currentValue = array_search($product->getVisibility(), $array);
                        $product->setVisibility($currentValue);

                        $categories = explode(",", $data['categories']);
                        $categoryIds = [];
                        $i=0;
                        foreach ($categories as $categor) {
                            $category = explode("/", $categor);
                            $categer = end($category);
                            $collection = $this->categoryFactory->create()->getCollection()->addAttributeToFilter('name', $categer)->setPageSize(1);
                            if ($collection->getSize()) {
                                $categoryId = $collection->getFirstItem()->getId();
                                $categoryIds[$i] = $categoryId;
                                $i++;
                            }
                        }

                        $manual_date = "2020-06-29 01:00:00";
//                        if (isset($data['created_at'])) {
//                            $date = explode("/", $data['created_at']);
//                            $dateYear = explode(",", $date[2]);
//                            $time_in_24_hour_format  = date("H:i:s", strtotime($dateYear[1]));
//
//                            $manual_date = '20' . $dateYear[0] . "-" . str_pad($date[1], 2, "0", STR_PAD_LEFT) . "-" . str_pad($date[0], 2, "0", STR_PAD_LEFT) . ' ' . $time_in_24_hour_format;
                        $product->setCreatedAt($manual_date);
                        //exit;
//                        }

                        $product->setCategoryIds($categoryIds);
                        $this->productRepository->save($product);

                        $this->categoryLinkManagement->assignProductToCategories($product->getSku(), $product->getCategoryIds());

                        echo "Add product : " . $name . "\n";
                    } catch (\Exception $e) {
                        $logger->info('Error importing product sku: ' . $sku . '. ' . $e->getMessage());
                        echo "Not add product : " . $name . '  ' . $e->getMessage() . "\n";
                        continue;
                    }

//                    try {
//                        $stockItem = $this->stockRegistry->getStockItemBySku($sku);
//                        $stockItem->setQty($qty);
//                        if ($qty > 0 || $productType == 'configurable') {
//                            $stockItem->setIsInStock(1);
//                        } else {
//                            $stockItem->setIsInStock(0);
//                        }
//                        $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
//
                    ////                        if ($stockItem->getQty() != $qty) {
                    ////                            $stockItem->setQty($qty);
                    ////                            if ($qty > 0) {
                    ////                                $stockItem->setIsInStock(1);
                    ////                            }
                    ////                            $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
                    ////                        }
//                    } catch (\Exception $e) {
//                        $logger->info('Error importing stock for product sku: ' . $sku . '. ' . $e->getMessage());
//                        echo "Error importing stock for product sku: " . $sku . '  ' . $e->getMessage() . "\n";
//                        continue;
//                    }

                    $sources =  [
                    0 => 'default',
                    1 => 'eazy',
                    2 => 'sample',
                ];

                    $sourceItem = $this->sourceItemFactory->create();

                    if ($this->product->getIdBySku($sku)) {
                        try {
                            foreach ($sources as $source) {
                                $sourceItem->setSourceCode($source);
                                $sourceItem->setSku($sku);
                                $sourceItem->setQuantity(0);
                                $sourceItem->setStatus(0);

                                $this->sourceItemsSaveInterface->execute([$sourceItem]);
                            }
                            echo "Importing stock for product name: " . $name . "\n";
                        } catch (\Exception $e) {
                            $logger->info('Error importing stock for product sku: ' . $sku . '. ' . $e->getMessage());
                            echo "Error importing stock for product sku: " . $sku . '  ' . $e->getMessage() . "\n";
                            continue;
                        }
                    }
                }
            }
            unset($product);
        }
        fclose($file);
    }
}
