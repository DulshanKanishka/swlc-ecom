<?php
namespace NeoSolax\DataImport\Console\Command;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
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
use Magento\Store\Model\ResourceModel\Website\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ImportUPsells extends Command
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
    /**
     * @var Magento\Catalog\Api\Data\ProductLinkInterfaceFactory
     */
    private $productLinks;

    public function __construct(
        StoreManagerInterface $storeManager,
        ProductLinkInterfaceFactory $productLinks,
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
        $this->setName('assign:upsells');
        $this->setDescription('Assign upsell link product to product');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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

                $name = $data['name'];

                if ($this->product->getIdBySku($sku) && ($data['upsell_skus'] || $data['related_skus'])) {
                    $product = $this->productRepository->getById($this->product->getIdBySku($sku));
                    $this->storeManager->setCurrentStore(0);
                    try {
                        if (!$product->getUpSellProducts()) {
                            $linkDataAll = [];
                            $skuLinks = $data['upsell_skus'];
                            $posision = $data['upsell_position'];
                            $skuLinks = explode(",", $skuLinks);
                            $posision = explode(",", $posision);

                            $k = 0;
                            foreach ($skuLinks as $skuLink) {
                                //check first that the product exist
                                $linkedProduct = $this->productFactory->create()->loadByAttribute("sku", $skuLink);
                                if ($linkedProduct) {
                                    $linkData = $this->productLinks->create();
                                    $linkData->setSku($sku);
                                    $linkData->setLinkedProductSku($skuLink);
                                    $linkData->setLinkType("upsell");
                                    $linkData->setPosition($posision[$k]);
                                    $linkDataAll[] = $linkData;
                                }
                                $k++;
                            }
                            if ($linkDataAll) {
//                            print(count($linkDataAll)); //gives 3
                                $product->setProductLinks($linkDataAll);
                                $this->productRepository->save($product);
                                echo "Add Up sell Product to : " . $name . "\n";
                            }
                        }
                        if (!$product->getRelatedProducts()) {
                            $linkDataRell = [];
                            $skuLinksR = $data['related_skus'];
                            $posisionR = $data['related_position'];
                            $skuLinksR = explode(",", $skuLinksR);
                            $posisionR = explode(",", $posisionR);

                            $k = 0;
                            foreach ($skuLinksR as $skuLink) {
                                //check first that the product exist
                                $linkedProduct = $this->productFactory->create()->loadByAttribute("sku", $skuLink);
                                if ($linkedProduct) {
                                    $linkData = $this->productLinks->create();
                                    $linkData->setSku($sku);
                                    $linkData->setLinkedProductSku($skuLink);
                                    $linkData->setLinkType("related");
                                    $linkData->setPosition($posisionR[$k]);
                                    $linkDataRell[] = $linkData;
                                }
                                $k++;
                            }
                            if ($linkDataRell) {
//                            print(count($linkDataAll)); //gives 3
                                $product->setProductLinks($linkDataRell);
                                $this->productRepository->save($product);
                                echo "Add Related Product to : " . $name . "\n";
                            }
                        }
                    } catch (\Exception $e) {
                        $logger->info('Error importing product sku: ' . $sku . '. ' . $e->getMessage());
                        echo "Not add Up sell Product to : " . $name . '  ' . $e->getMessage() . "\n";
                        continue;
                    }
                }
            }

            unset($product);
        }
        fclose($file);
    }
}
