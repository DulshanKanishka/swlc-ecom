<?php
namespace Dulshan\DataImport\Console\Command;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportProductTime extends Command
{
    protected $product;
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
        Product $products,
        Filesystem $filesystem,
        ResourceConnection $resource,
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        State $state,
        string $name = null
    ) {
        $this->storeManager = $storeManager;
        $this->product = $products;
        $this->filesystem = $filesystem;
        $this->resource = $resource;
        $this->state = $state;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('import:product:date');
        $this->setDescription('Import Product Date time');

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

                $name = $data['name'];
                $sku = $data['sku'];

                // used for setting the new product data
                if ($this->product->getIdBySku($sku)) {
                    $product = $this->productRepository->getById($this->product->getIdBySku($sku));
                    $this->storeManager->setCurrentStore(0);
                    try {
//                        if (isset($data['created_at'])) {
//                            $date = explode("/", $data['created_at']);
//                            $dateYear = explode(",", $date[2]);
//                            $time_in_24_hour_format = date("H:i:s", strtotime($dateYear[1]));
//
//                            $manual_date = '20' . $dateYear[0] . "-" . str_pad($date[1], 2, "0", STR_PAD_LEFT) . "-" . str_pad($date[0], 2, "0", STR_PAD_LEFT) . ' ' . $time_in_24_hour_format;
//                            $product->setCreatedAt($manual_date);
//                            //exit;
//                        }

                        $manual_date = "2020-04-20 01:00:00";
                        $product->setCreatedAt($manual_date);
                        $this->productRepository->save($product);

                        echo "Add product : " . $name . "\n";
                    } catch (\Exception $e) {
                        $logger->info('Error importing product sku: ' . $sku . '. ' . $e->getMessage());
                        echo "Not add product : " . $name . '  ' . $e->getMessage() . "\n";
                        continue;
                    }
                }
            }
            unset($product);
        }
        fclose($file);
    }
}
