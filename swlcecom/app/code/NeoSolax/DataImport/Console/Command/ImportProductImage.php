<?php
namespace NeoSolax\DataImport\Console\Command;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\EntryFactory;
use Magento\Catalog\Model\Product\Gallery\GalleryManagement;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\Api\ImageContentFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportProductImage extends Command
{
    protected $product;
    private $state;

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
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        ReadHandler $galleryReadHandler,
        Processor $imageProcessor,
        Gallery $productGallery,
        DirectoryList $directorylist,
        File $file,
        Product $products,
        Filesystem $filesystem,
        EntryFactory $mediaGalleryEntryFactory,
        GalleryManagement $mediaGalleryManagement,
        ImageContentFactory $imageContentFactory,
        State $state,
        string $name = null
    ) {
        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->productGallery = $productGallery;
        $this->imageProcessor = $imageProcessor;
        $this->galleryReadHandler = $galleryReadHandler;
        $this->directorylist = $directorylist;
        $this->file = $file;
        $this->product = $products;
        $this->filesystem = $filesystem;
        $this->state = $state;
        $this->mediaGalleryEntryFactory = $mediaGalleryEntryFactory;
        $this->mediaGalleryManagement = $mediaGalleryManagement;
        $this->imageContentFactory = $imageContentFactory;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('import:product:image');
        $this->setDescription('Import Product image from old Database to New Database');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

//        $connectionOld = $this->resource->getConnection('old_setup');
        $productArray[] = "0";

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
                $name = $data['name'];

                if ($this->product->getIdBySku($sku) && !in_array($this->product->getIdBySku($sku), $productArray)) {
                    $product = $this->productFactory->create()->load($this->product->getIdBySku($sku));
                    $this->storeManager->setCurrentStore(0);
                    $imgs = explode(',', $data['additional_images']);
                    $image = $data['base_image'];

                    $checkValid = explode('.', $image);

                    if (sizeof($checkValid) > 2) {
                        echo "Invalid Image for product name: " . $name . ' Image is ' . $image . "\n";
                        continue;
                    }

                    $this->removeImage($product);
                    try {
                        $this->productRepository->save($product);
                    } catch (\Exception $e) {
                        $logger->info('Error importing Image for product sku: ' . $sku . '. ' . $e->getMessage());
                        echo "Error importing Image for product sku: " . $sku . '  ' . $e->getMessage() . "\n";
                        continue;
                    }

                    $urlBase = $this->getMediaDirTmpDir() . 'temp/catalog/product' . $image;
//                    $entry = $this->mediaGalleryEntryFactory->create();

                    try {
                        if ($data['base_image']) {
                            $entry = $this->mediaGalleryEntryFactory->create();

                            if (file_exists($urlBase)) {
                                $entry->setFile($urlBase);
                                $entry->setMediaType('image');
                                $entry->setDisabled(false);
                                $entry->setTypes(['image']);

                                $imageContent = $this->imageContentFactory->create();
                                $imageContent
                                    ->setType(mime_content_type($urlBase))
                                    ->setName(baseName($image))
                                    ->setBase64EncodedData(base64_encode(file_get_contents($urlBase)));

                                $entry->setContent($imageContent);

                                $this->mediaGalleryManagement->create($sku, $entry);

//                                echo "Importing image for : " . $name . ' image name ' . baseName($image) . "\n";
                            }
                        }
                        if ($data['thumbnail_image']) {
                            $image = $data['thumbnail_image'];
                            $url = $this->getMediaDirTmpDir() . 'temp/catalog/product' . $image;

                            $checkValid = explode('.', $image);
                            if (sizeof($checkValid) > 2) {
                                echo "Invalid Image for product name: " . $name . ' Image is ' . $image . "\n";
                                continue;
                            }


                            if ($data['thumbnail_image'] == $data['base_image']) {
                                $this->removeImage($product);
                                $product->addImageToMediaGallery($urlBase, ['image', 'thumbnail'], false, false);
                            } else {
                                if (file_exists($url)) {
                                    $entry = $this->mediaGalleryEntryFactory->create();
                                    $entry->setFile($url);
                                    $entry->setMediaType('image');
                                    $entry->setDisabled(false);
                                    $entry->setTypes(['thumbnail']);

                                    $imageContent = $this->imageContentFactory->create();
                                    $imageContent
                                        ->setType(mime_content_type($url))
                                        ->setName(baseName($image))
                                        ->setBase64EncodedData(base64_encode(file_get_contents($url)));

                                    $entry->setContent($imageContent);

                                    $this->mediaGalleryManagement->create($sku, $entry);

//                            echo "Importing image for : " . $name . ' image name ' . baseName($image) . "\n";
                                }
                            }
                        }
                        if ($data['small_image']) {
                            $image = $data['small_image'];
                            $url = $this->getMediaDirTmpDir() . 'temp/catalog/product' . $image;

                            $checkValid = explode('.', $image);
                            if (sizeof($checkValid) > 2) {
                                echo "Invalid Image for product name: " . $name . ' Image is ' . $image . "\n";
                                continue;
                            }

                            if ($data['small_image'] == $data['base_image'] && $data['thumbnail_image'] == $data['base_image']) {
                                $this->removeImage($product);
                                $product->addImageToMediaGallery($urlBase, ['image', 'thumbnail', 'small_image'], false, false);
                            } elseif ($data['small_image'] == $data['base_image'] && $data['thumbnail_image'] != $data['base_image']) {
                                $this->removeImage($product);
                                $product->addImageToMediaGallery($urlBase, ['image', 'small_image'], false, false);
                            } else {
                                if (file_exists($url)) {
                                    $entry = $this->mediaGalleryEntryFactory->create();
                                    $entry->setFile($url);
                                    $entry->setMediaType('image');
                                    $entry->setDisabled(false);
                                    $entry->setTypes(['small_image']);

                                    $imageContent = $this->imageContentFactory->create();
                                    $imageContent
                                        ->setType(mime_content_type($url))
                                        ->setName(baseName($image))
                                        ->setBase64EncodedData(base64_encode(file_get_contents($url)));

                                    $entry->setContent($imageContent);

                                    $this->mediaGalleryManagement->create($sku, $entry);

//                            echo "Importing image for : " . $name . ' image name ' . baseName($image) . "\n";
                                }
                            }
                        }
                        if ($data['swatch_image']) {
                            $image = $data['swatch_image'];
                            $url = $this->getMediaDirTmpDir() . 'temp/catalog/product' . $image;


                            $checkValid = explode('.', $image);
                            if (sizeof($checkValid) > 2) {
                                echo "Invalid Image for product name: " . $name . ' Image is ' . $image . "\n";
                                continue;
                            }


                            if ($data['swatch_image'] == $data['base_image'] && $data['small_image'] == $data['base_image'] && $data['thumbnail_image'] == $data['base_image']) {
                                $this->removeImage($product);
                                $product->addImageToMediaGallery($urlBase, ['image', 'thumbnail', 'small_image', 'swatch_image'], false, false);
                            } elseif ($data['swatch_image'] == $data['base_image'] && $data['small_image'] == $data['base_image'] && $data['thumbnail_image'] != $data['base_image']) {
                                $this->removeImage($product);
                                $product->addImageToMediaGallery($urlBase, ['image', 'small_image', 'swatch_image'], false, false);
                            } elseif ($data['swatch_image'] == $data['base_image'] && $data['thumbnail_image'] == $data['base_image'] && $data['small_image'] != $data['base_image']) {
                                $this->removeImage($product);
                                $product->addImageToMediaGallery($urlBase, ['image', 'thumbnail', 'swatch_image'], false, false);
                            } elseif ($data['swatch_image'] == $data['base_image'] && $data['thumbnail_image'] == $data['base_image'] && $data['small_image'] != $data['base_image']&& $data['thumbnail_image'] != $data['base_image']) {
                                $this->removeImage($product);
                                $product->addImageToMediaGallery($urlBase, ['image', 'swatch_image'], false, false);
                            } else {
                                if (file_exists($url)) {
                                    $entry = $this->mediaGalleryEntryFactory->create();
                                    $entry->setFile($url);
                                    $entry->setMediaType('image');
                                    $entry->setDisabled(false);
                                    $entry->setTypes(['swatch_image']);

                                    $imageContent = $this->imageContentFactory->create();
                                    $imageContent
                                        ->setType(mime_content_type($url))
                                        ->setName(baseName($image))
                                        ->setBase64EncodedData(base64_encode(file_get_contents($url)));

                                    $entry->setContent($imageContent);

                                    $this->mediaGalleryManagement->create($sku, $entry);

//                            echo "Importing image for : " . $name . ' image name ' . baseName($image) . "\n";
                                }
                            }
                        }
                        if (sizeof($imgs) > 0) {
                            for ($i=0; $i<sizeof($imgs); $i++) {
                                if ($imgs[$i] != $data['base_image']) {
                                    $image_directory = $this->getMediaDirTmpDir() . 'temp/catalog/product' . $imgs[$i];
                                    if (!file_exists($image_directory)) {
                                        $image_directory = $this->getMediaDirTmpDir() . 'temp/catalog/product' . explode('.', $imgs[$i])[0] . '_1' . '.' . explode('.', $imgs[$i])[1];
                                    } elseif (!file_exists($image_directory)) {
                                        $image_directory = $this->getMediaDirTmpDir() . 'temp/catalog/product' . explode('.', $imgs[$i])[0] . '_1_1' . '.' . explode('.', $imgs[$i])[1];
                                    } elseif (!file_exists($image_directory)) {
                                        $image_directory = $this->getMediaDirTmpDir() . 'temp/catalog/product' . explode('.', $imgs[$i])[0] . '_1_2' . '.' . explode('.', $imgs[$i])[1];
                                    } elseif (!file_exists($image_directory)) {
                                        $image_directory = $this->getMediaDirTmpDir() . 'temp/catalog/product' . explode('.', $imgs[$i])[0] . '_2' . '.' . explode('.', $imgs[$i])[1];
                                    }




                                    $checkValid = explode('.', $imgs[$i]);
                                    if (sizeof($checkValid) > 2) {
                                        echo "Invalid Image for product name: " . $name . ' Image is ' . $imgs[$i] . "\n";
                                        continue;
                                    }

                                    $entry = $this->mediaGalleryEntryFactory->create();
                                    if (file_exists($image_directory)) {
                                        $entry->setFile($image_directory);
                                        $entry->setMediaType('image');
                                        $entry->setDisabled(false);
                                        $entry->setTypes([null]);

                                        $imageContent = $this->imageContentFactory->create();
                                        $imageContent
                                            ->setType(mime_content_type($image_directory))
                                            ->setName(baseName($imgs[$i]))
                                            ->setBase64EncodedData(base64_encode(file_get_contents($image_directory)));

                                        $entry->setContent($imageContent);
//                                        echo "Importing image for : " . $name . ' image name ' . baseName($urlBase) . "\n";

                                        $product->getMediaGalleryEntries();
                                        $imageRoles  = [];
                                        $product->addImageToMediaGallery($image_directory, $imageRoles, true, false);
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $logger->info('Error importing Image for product sku: ' . $sku . '. ' . $e->getMessage());
                        echo "Error importing Image for product sku: " . $sku . '  ' . $e->getMessage() . "\n";
                        continue;
                    }
                    $this->productRepository->save($product);
                    echo "Importing image for : " . $name . ' image name ' . baseName($urlBase) . "\n";
                }
                $productArray[] = $this->product->getIdBySku($sku);
            }
            unset($product);
        }
        fclose($file);
    }
    protected function getMediaDirTmpDir()
    {
        return $this->directorylist->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR;
    }

    protected function removeImage($product)
    {
        $existingMediaGalleryEntries = $product->getMediaGalleryEntries();

        foreach ($existingMediaGalleryEntries as $key => $entry) {
            //We can add your condition here
            unset($existingMediaGalleryEntries[$key]);
        }

        $product->setMediaGalleryEntries($existingMediaGalleryEntries);
    }
}
