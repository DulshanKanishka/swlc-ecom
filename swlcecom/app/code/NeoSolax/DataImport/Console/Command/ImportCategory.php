<?php
namespace NeoSolax\DataImport\Console\Command;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCategory extends Command
{
    protected $stockRegistry;
    private $state;
    private $resource;
    private $filesystem;
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    public function __construct(
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
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
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('import:category');
        $this->setDescription('Import Category from old Database to New Database');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        define('DS', DIRECTORY_SEPARATOR);
        $file = fopen('csv/Category.csv', 'r', '"'); // set path to the CSV file

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
                $image = $data['image'];
                $ParentName = $data['parent_name'];

                $cate = $this->category->getCollection()->addAttributeToFilter('name', $name)->getFirstItem();

                // used for setting the new product data
                $category = $this->categoryFactory->create();
                if ($name != "") {
                    try {
                        $this->storeManager->setCurrentStore(0);
                        $category->setStoreId(0);
                        if ($ParentName == 'Default Category') {
                            $categoryID = 2;
                            $category->setParentId($categoryID);
//                            $rootCat = $this->category->load($categoryID);
//                            $category->setPath($rootCat->getPath());
                        } else {
                            $collection = $this->categoryFactory->create()->getCollection()->addAttributeToFilter('name', $ParentName)->setPageSize(1);
                            $categoryID = $collection->getFirstItem()->getId();
                            $category->setParentId($categoryID);
//                            $rootCat = $this->category->load($categoryID);
//                            $category->setPath($rootCat->getPath());
                        }


                        if ($ParentName == "") {
                            $category->setAttributeSetId(0);
                        } else {
                            $category->setAttributeSetId(3);
                        }



                        $i = 0;
                        foreach ($data as $value) {
                            if ($header[$i] != 'parent_name' || $header[$i] != 'categoryPath') {
                                $category->setData($header[$i], $value);
                            }
                            $i++;
                        }

                        $this->categoryRepository->save($category);


                        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
                        $mediaPath = $mediaDirectory->getAbsolutePath();
                        $url = $mediaPath . 'catalog/category/' . $image;

                        if ($image) {
                            $mediaAttribute = ['image', 'small_image', 'thumbnail'];
                            $category->setImage($url, $mediaAttribute, true, false);// Path pub/meida/catalog/category/
                            $category->save();
                        }

                        $categoryPath = $data['categoryPath'];
                        $split = explode("/", $categoryPath);

                        $path = [];
                        $x = 0;
                        foreach ($split as $item) {
                            if ($item == 'Root Catalog Migrated') {
                                $item = 'Default Category';
                            }
                            $collection = $this->category->getCollection()->addAttributeToFilter('name', $item)->getFirstItem();
                            $categoryID = $collection->getId();
                            if ($categoryID != null || $categoryID != "") {
                                $path[$x] = $categoryID;
                            }
                            $x++;
                        }

                        $pathAT = implode("/", $path);
                        $category->setPath($pathAT);

                        $category->save();

                        $this->categoryRepository->save($category);

                        echo 'Category ' . $category->getName() . ' ' . $category->getId() . ' imported successfully' . PHP_EOL;
                    } catch (\Exception $e) {
                        $logger->info('Error importing Category sku: ' . $name . '. ' . $e->getMessage());
                        echo "Not add Category : " . $name . '  ' . $e->getMessage() . "\n";
                        continue;
                    }
                }
            }
            unset($category);
        }

        fclose($file);
    }
}
