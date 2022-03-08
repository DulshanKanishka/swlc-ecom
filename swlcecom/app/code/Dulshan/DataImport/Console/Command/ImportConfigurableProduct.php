<?php
namespace Dulshan\DataImport\Console\Command;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportConfigurableProduct extends Command
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

    private $optionsFactory;

    /**
     * ImportConfigurableProduct constructor.
     * @param Factory $optionsFactory
     * @param Product $productCollection
     * @param Filesystem $filesystem
     * @param ResourceConnection $resource
     * @param ProductInterfaceFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StockRegistryInterface $stockRegistry
     * @param State $state
     * @param string $name
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Configurable $configurable,
        ProductLinkInterface $productLinks,
        CategoryLinkManagementInterface $categoryLinkManagementInterface,
        CategoryFactory $categoryFactory,
        CollectionFactory $websiteCollectionFactory,
        AttributeFactory $eavAttributeFactory,
        AttributeOptionManagementInterface $attributeOptionManagement,
        Factory $optionsFactory,
        Product $productCollection,
        Filesystem $filesystem,
        ResourceConnection $resource,
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        State $state,
        string $name = null
    ) {
        $this->storeManager = $storeManager;
        $this->configurable = $configurable;
        $this->productLinks = $productLinks;
        $this->categoryLinkManagement = $categoryLinkManagementInterface;
        $this->categoryFactory = $categoryFactory;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->eavAttributeFactory = $eavAttributeFactory;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->optionsFactory = $optionsFactory;
        $this->product = $productCollection;
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
        $this->setName('import:configurable:product');
        $this->setDescription('Import Configurable Product from old Database to New Database');

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

//                $configurableProduct = $this->configurable->getConfigurableAttributes($product);
//                && !$configurableProduct->getData()
//                $array =  [
//                    0 => 'Elite Long Sleeve Tee',
//                    1 => 'Elite Zip Neck Polo',
//                    2 => 'Mens Raglan Pullover Hoodie Heather Grey',
//                    3 => 'Mens Short',
//                    4 => 'Clipper Short CS',
//                    5 => 'St. Christopher Athletic Shorts | Girls',
//                ];

                if ($this->product->getIdBySku($sku) && $productType == 'configurable') {
                    $product = $this->productRepository->getById($this->product->getIdBySku($sku));

                    try {
                        $this->storeManager->setCurrentStore(0);
                        $attributeValues = [];
                        $associatedProductIds = [];
                        $AssingColor = "";
                        $AssingValue = "";
                        if ($name == 'Rugby Shirt Style C | Sky/Navy') {
                            $configurable_variations = "sku=1-STK-6-111136-SKY/NVY-S,size=S,size=S";
                        } else {
                            $configurable_variations = $data['configurable_variations'];
                        }
                        $string = explode("|", $configurable_variations);

                        if ($name == 'St. Christopher Athletic Shorts | Girls') {
                            $string = [
                                0 => 'sku=St. Christopher Athletic Shorts | Girls-SG,size=SG,size=SG',
                                1 => 'sku=St. Christopher Athletic Shorts | Girls-MG,size=MG,size=MG',
                                2 => 'sku=St. Christopher Athletic Shorts | Girls-8,size=8,size=8',
                                3 => 'sku=St. Christopher Athletic Shorts | Girls-10,size=10,size=10',
                                4 => 'sku=St. Christopher Athletic Shorts | Girls-12,size=12,size=12',
                            ];
                        }
                        foreach ($string as $a) {
                            $aa = explode(",", $a);
                            $bbb = explode("sku=", $aa[0]);
                            $AssingProductArray = $this->product->getIdBySku($bbb[1]);
                            foreach ($aa as $item) {
                                $value = explode("=", $item);
                                if ($value[0] == 'size') {
                                    $AssingValue = $value[1];
                                }
                                if ($value[0] == 'color') {
                                    $AssingColor = $value[1];
                                }
                            }
                            $connection = $this->resource->getConnection();
                            $tableName = $this->resource->getTableName('eav_attribute_option_value');
                            $productAA = $this->productRepository->getById($AssingProductArray);
                            $attrSizeNeww = $productAA->getResource()->getAttribute('size');
                            $option_name = $attrSizeNeww->getSource()->getOptionText($productAA->getSize());
                            if ($AssingValue) {
                                if (!$attrSizeNeww->getSource()->getOptionId($AssingValue)) {
                                    $magentoAttribute = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'size');
                                    $attributeCode = $magentoAttribute->getAttributeCode();
                                    $magentoAttributeOptions = $this->attributeOptionManagement->getItems(
                                        'catalog_product',
                                        $attributeCode
                                    );
                                    $attributeOptions = [$AssingValue];
                                    $existingMagentoAttributeOptions = [];
                                    $newOptions = [];
                                    $counter = 0;
                                    foreach ($magentoAttributeOptions as $option) {
                                        if (!$option->getValue()) {
                                            continue;
                                        }
                                        if ($option->getLabel() instanceof \Magento\Framework\Phrase) {
                                            $label = $option->getText();
                                        } else {
                                            $label = $option->getLabel();
                                        }
                                        if ($label == '') {
                                            continue;
                                        }
                                        $existingMagentoAttributeOptions[] = $label;
                                        $newOptions['value'][$option->getValue()] = [$label, $label];
                                        $counter++;
                                    }
                                    foreach ($attributeOptions as $option) {
                                        if ($option == '') {
                                            continue;
                                        }
                                        if (!in_array($option, $existingMagentoAttributeOptions)) {
                                            $newOptions['value']['option_' . $counter] = [$option, $option];
                                        }
                                        $counter++;
                                    }
                                    if (count($newOptions)) {
                                        $magentoAttribute->setOption($newOptions)->save();
                                    }
                                }
                            }
                            if ($AssingValue != $option_name) {
                                $option_idd = $attrSizeNeww->getSource()->getOptionId($AssingValue);
                                if (!$option_idd && $AssingValue) {
                                    $sql = "select * FROM " . $tableName . " where value='" . $AssingValue . "'";
                                    $result = $connection->fetchAll($sql);
                                    $option_idd = $result[0]['option_id'];
                                }
                                $productAA->setSize($option_idd);
                                $productAA->save();
                            }

                            $attrColorNeww = $productAA->getResource()->getAttribute('color');
                            $option_color = $attrColorNeww->getSource()->getOptionText($productAA->getColor());

                            if ($AssingColor) {
                                if (!$attrColorNeww->getSource()->getOptionId($AssingColor)) {
                                    $magentoAttribute = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'color');
                                    $attributeCode = $magentoAttribute->getAttributeCode();
                                    $magentoAttributeOptions = $this->attributeOptionManagement->getItems(
                                        'catalog_product',
                                        $attributeCode
                                    );
                                    $attributeOptions = [$AssingColor];
                                    $existingMagentoAttributeOptions = [];
                                    $newOptions = [];
                                    $counter = 0;
                                    foreach ($magentoAttributeOptions as $option) {
                                        if (!$option->getValue()) {
                                            continue;
                                        }
                                        if ($option->getLabel() instanceof \Magento\Framework\Phrase) {
                                            $label = $option->getText();
                                        } else {
                                            $label = $option->getLabel();
                                        }
                                        if ($label == '') {
                                            continue;
                                        }
                                        $existingMagentoAttributeOptions[] = $label;
                                        $newOptions['value'][$option->getValue()] = [$label, $label];
                                        $counter++;
                                    }
                                    foreach ($attributeOptions as $option) {
                                        if ($option == '') {
                                            continue;
                                        }
                                        if (!in_array($option, $existingMagentoAttributeOptions)) {
                                            $newOptions['value']['option_' . $counter] = [$option, $option];
                                        }
                                        $counter++;
                                    }
                                    if (count($newOptions)) {
                                        $magentoAttribute->setOption($newOptions)->save();
                                    }
                                }
                            }

                            if ($AssingColor != $option_color) {
                                $option_idd = $attrColorNeww->getSource()->getOptionId($AssingColor);
                                if (!$option_idd && $AssingColor) {
                                    $sql = "select * FROM " . $tableName . " where value='" . $AssingColor . "'";
                                    $result = $connection->fetchAll($sql);
                                    $option_idd = $result[0]['option_id'];
                                }
                                $productAA->setColor($option_idd);
                                $productAA->save();
                            }

                            if ($AssingValue) {
                                $attributeValues[] = [
                                    'label' => $AssingValue,
                                    'attribute_id' => $product->getResource()->getAttribute('size')->getId(),
                                    'value_index' => $AssingValue,
                                ];
//                                $associatedProductIds[] = $this->product->getIdBySku($bbb[1]);
                            }
                            if ($AssingColor) {
                                $attributeColor[] = [
                                    'label' => $AssingColor,
                                    'attribute_id' => $product->getResource()->getAttribute('color')->getId(),
                                    'value_index' => $AssingColor,
                                ];
//                                $associatedProductIds[] = $this->product->getIdBySku($bbb[1]);
                            }
                            $associatedProductIds[] = $this->product->getIdBySku($bbb[1]);
                        }

                        if ($AssingColor) {
                            $configurableAttributesData = [
                                [
                                    'attribute_id' => $product->getResource()->getAttribute('color')->getId(),
                                    'code' => $product->getResource()->getAttribute('color')->getAttributeCode(),
                                    'label' => $product->getResource()->getAttribute('color')->getStoreLabel(),
                                    'position' => '0',
                                    'values' => $attributeColor,
                                ],
                                [
                                    'attribute_id' => $product->getResource()->getAttribute('size')->getId(),
                                    'code' => $product->getResource()->getAttribute('size')->getAttributeCode(),
                                    'label' => $product->getResource()->getAttribute('size')->getStoreLabel(),
                                    'position' => '0',
                                    'values' => $attributeValues,
                                ],

                            ];
                        } elseif (!$AssingColor) {
                            $configurableAttributesData = [
                                [
                                    'attribute_id' => $product->getResource()->getAttribute('size')->getId(),
                                    'code' => $product->getResource()->getAttribute('size')->getAttributeCode(),
                                    'label' => $product->getResource()->getAttribute('size')->getStoreLabel(),
                                    'position' => '0',
                                    'values' => $attributeValues,
                                ],
                            ];
                        }
//                        $product_size = $attrSizeNeww->getSource()->getOptionText($product->getSize());
//                        $product_color = $attrColorNeww->getSource()->getOptionText($product->getColor());
//
//                        if (!$product_size) {
//                            $option_idd = $attrSizeNeww->getSource()->getOptionId('M');
//                            $product->setSize($option_idd);
//                            $this->productRepository->save($product);
//                        }

                        $configurableOptions = $this->optionsFactory->create($configurableAttributesData);
                        $extensionConfigurableAttributes = $product->getExtensionAttributes();
                        $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
                        $extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
                        $product->setExtensionAttributes($extensionConfigurableAttributes);

                        $this->productRepository->save($product);

                        echo "Add Configurable values to : " . $name . "\n";
                    } catch (\Exception $e) {
                        $logger->info('Error importing product sku: ' . $sku . '. ' . $e->getMessage());
                        echo "Not add Configurable values to  : " . $name . '  ' . $e->getMessage() . "\n";
                        continue;
                    }
                }
            }
            unset($product);
        }

        fclose($file);
    }
}
