<?php
namespace NeoSolax\DataImport\Console\Command;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportStockQty extends Command
{
    protected $product;
    private $state;
    protected $stockRegistry;
    private $productFactory;
    private $productRepository;
    private $filesystem;

    public function __construct(
        StoreManagerInterface $storeManager,
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        SourceItemInterfaceFactory $sourceItemFactory,
        StockRegistryInterface $stockRegistry,
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        File $file,
        Product $products,
        Filesystem $filesystem,
        State $state,
        string $name = null
    ) {
        $this->storeManager = $storeManager;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->file = $file;
        $this->product = $products;
        $this->stockRegistry = $stockRegistry;
        $this->filesystem = $filesystem;
        $this->state = $state;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('import:product:stock');
        $this->setDescription('Import Product Stock from old Database to New Database');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $productArray[] = "0";
        $file = fopen('csv/Stock.csv', 'r', '"'); // set path to the CSV file

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
                $this->storeManager->setCurrentStore(0);
                if ($data_count < 1) {
                    continue;
                }

                $data = [];
                $data = array_combine($header, $row);

                $sku = $data['product_sku'];
                $name = $data['product_name'];
                $source = $data['warehouse'];
                $totalQty = $data['qty_in_warehouse'];
                $shelfLocation = $data['shelf_location'];

                if ($source == 'J20') {
                    $source = 'default';
                }

                if ($source == 'EAZY') {
                    $source = 'eazy';
                }

                if ($source == 'SAMPLE') {
                    $source = 'sample';
                }

                $sourceItem = $this->sourceItemFactory->create();

                if ($this->product->getIdBySku($sku)) {
                    try {
                        $sourceItem->setSourceCode($source);
                        $sourceItem->setSku($sku);
                        $sourceItem->setQuantity($totalQty);
                        if ($source == 'sample' || $source == 'eazy') {
                            $sourceItem->setStatus(0);
                        } else {
                            $sourceItem->setStatus(1);
                        }
                        $sourceItem->setShelfLocation($shelfLocation);
                        $this->sourceItemsSaveInterface->execute([$sourceItem]);

                        $searchCriteria = $this->searchCriteriaBuilder
                            ->addFilter(SourceItemInterface::SKU, $sku)
                            ->create();
                        $result = $this->sourceItemRepository->getList($searchCriteria)->getItems();

                        $sourceCode = $source;
                        foreach ($result as $value) {
                            if ($value->getSourceCode() == $sourceCode) {
                                $value->setShelfLocation($shelfLocation);
                                $value->save();
                                break;
                            }
                        }

                        echo "Importing stock for product name: " . $name . "\n";
                    } catch (\Exception $e) {
                        $logger->info('Error importing stock for product sku: ' . $sku . '. ' . $e->getMessage());
                        echo "Error importing stock for product sku: " . $sku . '  ' . $e->getMessage() . "\n";
                        continue;
                    }
                }
            }
            unset($product);
        }
        fclose($file);
    }
}
