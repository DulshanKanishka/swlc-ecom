<?php
namespace Dulshan\DataImport\Console\Command;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Model\Config;
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

class ImportAditionalOption extends Command
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
     * ImportAditionalOption constructor.
     * @param Attribute $attribute
     * @param EavSetup $eavSetup
     * @param Media $swatchHelper
     * @param File $driverFile
     * @param \Magento\Catalog\Model\Product\Media\Config $productMediaConfig
     * @param Config $eavConfig
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
        Attribute $attribute,
        EavSetup $eavSetup,
        Media $swatchHelper,
        File $driverFile,
        \Magento\Catalog\Model\Product\Media\Config $productMediaConfig,
        Config $eavConfig,
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
        $this->setName('import:options');
        $this->setDescription('Import Option from old Database to New Database');

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
            $required_data_fields = 3;
            $orderc = 0;
            $orders = 0;

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
                        $attributeOptions = [$data['admin_label']];
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
                            $counter++;
                        }
                        if ($attributeOptions == '') {
                            continue;
                        }
                        if (!in_array($option, $existingMagentoAttributeOptions)) {
                            $newOptions['optionvisual']['value'][0] = [$data['admin_label'], $data['front_label']];
                            $newOptions['optionvisual']['order'][0] = ++$orderc;
                            if (count($newOptions)) {
                                $attribute->addData($newOptions)->save();
                            }
                        }
                        echo "Add swatch : " . $data['admin_label'] . "\n";
                    }
                    if ($data['atribute_name'] == 'size') {
                        $attribute = $this->eavConfig->getAttribute('catalog_product', 'size');
                        $option['attribute_id'] = $attribute->getAttributeId();

                        $magentoAttribute = $this->eavAttributeFactory->create()->loadByCode('catalog_product', 'size');
                        $attributeCode = $magentoAttribute->getAttributeCode();
                        $magentoAttributeOptions = $this->attributeOptionManagement->getItems(
                            'catalog_product',
                            $attributeCode
                        );
                        $attributeOptions = [$data['admin_label']];
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
                            $counter++;
                        }
                        if ($attributeOptions == '') {
                            continue;
                        }
                        if (!in_array($option, $existingMagentoAttributeOptions)) {
                            $newOptions['optiontext']['value'][0] = [$data['admin_label'], $data['front_label']];
                            $newOptions['optiontext']['order'][0] = ++$orders;
                            if (count($newOptions)) {
                                $attribute->addData($newOptions)->save();
                            }
                        }
                        echo "Add swatch : " . $data['admin_label'] . "\n";
                    }
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
        return $this->directorylist->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR;
    }
}
