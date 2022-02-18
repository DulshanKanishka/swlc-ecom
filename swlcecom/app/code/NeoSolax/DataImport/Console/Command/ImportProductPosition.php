<?php
namespace NeoSolax\DataImport\Console\Command;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportProductPosition extends Command
{
    protected $stockRegistry;
    private $state;
    private $resource;
    private $filesystem;
    private $categoryRepository;

    public function __construct(
        StoreManagerInterface $storeManager,
        Product $productRepository,
        ResourceConnection $resourceConnection,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        Category $category,
        CategoryFactory $categoryFactory,
        Filesystem $filesystem,
        ResourceConnection $resource,
        State $state,
        string $name = null
    ) {
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->state = $state;
        $this->category = $category;
        $this->categoryFactory= $categoryFactory;
        $this->filesystem = $filesystem;
        $this->resource = $resource;
        $this->state = $state;
        $this->productRepository = $productRepository;
        $this->getConnection = $resourceConnection->getConnection();
        $this->categoryProductTable = $resourceConnection->getTableName('catalog_category_product');

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('import:product:position');
        $this->setDescription('Import Product Position from old Database to New Database');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        define('DS', DIRECTORY_SEPARATOR);
        $file = fopen('csv/Postion.csv', 'r', '"'); // set path to the CSV file

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

                $categoryName = $data['category_name'];
                $sku = $data['product_sku'];
                $position = $data['position'];
                if ($position == "0") {
                    $position = "1";
                }

                $cate = $this->category->getCollection()->addAttributeToFilter('name', $categoryName)->getFirstItem();

                // used for setting the new product data
                $category = $this->categoryFactory->create();
                if ($categoryName != "") {
                    try {
                        $adapter = $this->getConnection;
                        //Update product position using csv
                        if ($sku) {
                            $productId = $this->productRepository->getIdBySku($sku);
                            $this->storeManager->setCurrentStore(0);
                            if ($productId) {
                                $where = [
                                        'category_id = ?' => (int)$cate->getId(),
                                        'product_id = ?' => (int)$productId
                                    ];
                                $bind = ['position' => (int)$position];
                                $adapter->update($this->categoryProductTable, $bind, $where);
                                echo 'Position Updated Successfully.', $sku . "\n";
                            }
                        }
                    } catch (\Exception $e) {
                        $logger->info('Error importing Product sku: ' . $sku . '. ' . $e->getMessage());
                        echo "Not add Product : " . $sku . '  ' . $e->getMessage() . "\n";
                        continue;
                    }
                }
            }
            unset($category);
        }

        fclose($file);
    }
}
