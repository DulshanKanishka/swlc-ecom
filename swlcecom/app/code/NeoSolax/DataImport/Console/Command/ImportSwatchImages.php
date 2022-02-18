<?php
namespace NeoSolax\DataImport\Console\Command;

use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Swatches\Helper\Media;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportSwatchImages extends Command
{
    protected $product;
    protected $stockRegistry;
    private $state;
    private $resource;
    private $filesystem;

    /**
     * ImportSwatchImages constructor.
     * @param StoreManagerInterface $storeManager
     * @param Filesystem\Io\File $file
     * @param Repository $productAttributeRepository
     * @param Attribute $attribute
     * @param EavSetup $eavSetup
     * @param Media $swatchHelper
     * @param File $driverFile
     * @param Config $productMediaConfig
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param DirectoryList $directorylist
     * @param AttributeFactory $eavAttributeFactory
     * @param AttributeOptionManagementInterface $attributeOptionManagement
     * @param Filesystem $filesystem
     * @param ResourceConnection $resource
     * @param StockRegistryInterface $stockRegistry
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Filesystem\Io\File $file,
        Repository $productAttributeRepository,
        Attribute $attribute,
        EavSetup $eavSetup,
        Media $swatchHelper,
        File $driverFile,
        Config $productMediaConfig,
        \Magento\Eav\Model\Config $eavConfig,
        DirectoryList $directorylist,
        AttributeFactory $eavAttributeFactory,
        AttributeOptionManagementInterface $attributeOptionManagement,
        Filesystem $filesystem,
        ResourceConnection $resource,
        StockRegistryInterface $stockRegistry,
        State $state,
        string $name = null
    ) {
        $this->storeManager = $storeManager;
        $this->file = $file;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->attribute = $attribute;
        $this->directorylist = $directorylist;
        $this->eavSetup = $eavSetup;
        $this->driverFile = $driverFile;
        $this->swatchHelper = $swatchHelper;
        $this->productMediaConfig = $productMediaConfig;
        $this->eavConfig = $eavConfig;
        $this->eavAttributeFactory = $eavAttributeFactory;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->filesystem = $filesystem;
        $this->resource = $resource;
        $this->state = $state;
        $this->stockRegistry = $stockRegistry;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('import:option:image');
        $this->setDescription('Import Option  image from old Database to New Database');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = fopen('csv/Swatch.csv', 'r', '"'); // set path to the CSV file

        if ($file !== false) {
            $this->state->setAreaCode('frontend');

            // add logging capability
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/import-new.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);

            $header = fgetcsv($file); // get data headers and skip 1st row


            // enter the min number of data fields you require that the new product will have (only if you want to standardize the import)
            $count = 1;
            while ($row = fgetcsv($file, 3000, ",")) {
                $data_count = count($row);
                if ($data_count < 1) {
                    continue;
                }

                $data = [];
                $data = array_combine($header, $row);

                try {
                    $option = [];
                    $this->storeManager->setCurrentStore(0);
                    if ($data['atribute_name'] == 'color') {
                        $attribute = $this->eavConfig->getAttribute('catalog_product', 'color');
                        $option['attribute_id'] = $attribute->getAttributeId();

                        $magentoAttribute = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'color');
                        $attributeCode = $magentoAttribute->getAttributeCode();
                        $magentoAttributeOptions = $this->attributeOptionManagement->getItems(
                            'catalog_product',
                            $attributeCode
                        );
                        $newSwatch = [];
                        foreach ($magentoAttributeOptions as $option) {
                            $key = $option['value'];
                            $label = $option['label'];
                            $valueImage = $data['image'];
                            if ($label == $data['front_label']) {
                                if ($valueImage[0] == '/') {
                                    $newFile = $this->directorylist->getPath(DirectoryList::MEDIA) . '/temp/attribute/swatch' . $valueImage;
                                    $tmpDir = $this->getMediaDirTmpDir();
                                    $this->file->checkAndCreateFolder($tmpDir);
                                    $newFileName = $tmpDir . 'catalog/product/' . basename($valueImage);
                                    $result = $this->file->read($newFile, $newFileName);
                                    if ($result) {
                                        $newFiles = $this->swatchHelper->moveImageFromTmp(basename($valueImage));
                                        $this->swatchHelper->generateSwatchVariations($newFiles);
                                        $fileData = ['swatch_path' => $this->swatchHelper->getSwatchMediaUrl(), 'file_path' => $newFile];
                                        $newSwatch['swatchvisual']['value'][$key] = $valueImage;
                                    }
                                }
                                if ($valueImage[0] == '#') {
                                    $newSwatch['swatchvisual']['value'][$key] = $valueImage;
                                }
                            }

                            ++$count;
                        }
                        $attribute->addData($newSwatch)->save();
                    }

                    if ($data['atribute_name'] == 'size') {
                        $attributesize = $this->eavConfig->getAttribute('catalog_product', 'size');
                        $option['attribute_id'] = $attributesize->getAttributeId();

                        $magentoAttribute = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'size');
                        $attributeCode = $magentoAttribute->getAttributeCode();
                        $magentoAttributeOptions = $this->attributeOptionManagement->getItems(
                            'catalog_product',
                            $attributeCode
                        );
                        $newSwatch = [];
                        foreach ($magentoAttributeOptions as $option) {
                            $key = $option['value'];
                            $label = $option['label'];
                            if ($label == $data['admin_label']) {
                                $newSwatch['swatchtext']['value'][$key][0] = $label;
                                $newSwatch['swatchtext']['value'][$key][1] = $label;
                            }
                            ++$count;
                        }

                        $attributesize->addData($newSwatch)->save();
                    }

                    echo "Add swatch : " . $data['admin_label'] . "\n";
                } catch (\Exception $e) {
                    $logger->info('Error importing swatch: ' . $data['admin_label'] . '. ' . $e->getMessage());
                    echo "Not add swatch : " . $data['admin_label'] . '  ' . $e->getMessage() . "\n";
                    continue;
                }
            }

            unset($product);
        }
        fclose($file);
    }
    protected function getMediaDirTmpDir()
    {
        return $this->directorylist->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
    }
}
