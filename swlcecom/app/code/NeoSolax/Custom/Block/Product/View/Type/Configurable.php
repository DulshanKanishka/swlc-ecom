<?php

namespace NeoSolax\Custom\Block\Product\View\Type;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product as CatalogProduct;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\Format;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Swatches\Helper\Data as SwatchData;
use Magento\Swatches\Helper\Media;
use Magento\Swatches\Model\SwatchAttributesProvider;


class Configurable extends \WebPanda\ConfigurablePriceRange\Block\Product\Renderer\Configurable
{
    private $localeFormat;
    private $variationPrices;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    protected Product $productCollection;

    public function __construct
    (
        Context $context,
        ArrayUtils $arrayUtils,
        EncoderInterface $jsonEncoder,
        Data $helper,
        CatalogProduct $catalogProduct,
        CurrentCustomer $currentCustomer,
        PriceCurrencyInterface $priceCurrency,
        ConfigurableAttributeData $configurableAttributeData,
        SwatchData $swatchHelper,
        Media $swatchMediaHelper,
        ResourceConnection $resourceConnection,
        \Magento\Framework\Locale\Format $localeFormat,
        Product $productCollection,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices $variationPrices,
        array $data = [],
        SwatchAttributesProvider $swatchAttributesProvider = null,
        UrlBuilder $imageUrlBuilder = null
    )
    {
        $this->productCollection=$productCollection;
        $this->resourceConnection = $resourceConnection;
        $this->localeFormat = $localeFormat;
        $this->variationPrices = $variationPrices;
        parent::__construct($context, $arrayUtils, $jsonEncoder, $helper, $catalogProduct, $currentCustomer, $priceCurrency, $configurableAttributeData, $swatchHelper, $swatchMediaHelper, $data, $swatchAttributesProvider, $imageUrlBuilder);
    }

    public function getAllowProducts()
    {
        if (!$this->hasAllowProducts()) {
            $products = [];
            $skipSaleableCheck = true;//$this->catalogProduct->getSkipSaleableCheck(); // To Display out of stock items
            $allProducts = $this->getProduct()->getTypeInstance()->getUsedProducts($this->getProduct(), null);
            foreach ($allProducts as $product) {
                if ($product->isSaleable() || $skipSaleableCheck) {
                    $products[] = $product;
                }
            }
            $this->setAllowProducts($products);
        }
        return $this->getData('allow_products');
    }

    public function getJsonConfig()
    {
        $store = $this->getCurrentStore();
        $currentProduct = $this->getProduct();

        $options = $this->helper->getOptions($currentProduct, $this->getAllowProducts());
        $attributesData = $this->configurableAttributeData->getAttributesData($currentProduct, $options);

        $salableProduct =$options['salables'];

        foreach ($salableProduct as $key => $value) {
            $product = $this->productCollection->load($key);
            $sku = $product->getSku();
            $backorderStatus = $product->getExtensionAttributes()->getStockItem()->getBackorders();
            $checkInStock=$product->getQuantityAndStockStatus()['is_in_stock'];
            $checkIsEnabled=$product->getStatus()==1;
            $checkBackOrdersAndPreOrders= ($backorderStatus==1 || $backorderStatus==2 || $backorderStatus==101);
            if ($checkIsEnabled){
                $connection = $this->resourceConnection->getConnection();
                $salable = $connection->fetchAll("SELECT t1.`quantity` + COALESCE(SUM(t2.`quantity`), 0) salable_quantity FROM `inventory_source_item` t1 LEFT JOIN `inventory_reservation` t2 ON t1.`sku` = t2.`sku` AND t1.`status` = 1 WHERE t1.`sku` = '$sku' AND t1.source_code='default'");
                $salableqty = $salable[0]['salable_quantity'];
                if (($salableqty > 0 || $checkBackOrdersAndPreOrders)&& $checkInStock && $checkIsEnabled) {
                    $salableProduct[$key]=true;
                }
                else{
                    $salableProduct[$key]=false;
                }
            }

        }

        $config = [
            'attributes' => $attributesData['attributes'],
            'template' => str_replace('%s', '<%- data.price %>', $store->getCurrentCurrency()->getOutputFormat()),
            'currencyFormat' => $store->getCurrentCurrency()->getOutputFormat(),
            'optionPrices' => $this->getOptionPrices(),
            'priceFormat' => $this->localeFormat->getPriceFormat(),
            'prices' => $this->variationPrices->getFormattedPrices($this->getProduct()->getPriceInfo()),
            'productId' => $currentProduct->getId(),
            'isSalableOrNot' => isset($salableProduct) ? $salableProduct : [],
            'chooseText' => __('Choose an Option...'),
            'images' => $this->getOptionImages(),
            'index' => isset($options['index']) ? $options['index'] : [],
        ];

        if ($currentProduct->hasPreconfiguredValues() && !empty($attributesData['defaultValues'])) {
            $config['defaultValues'] = $attributesData['defaultValues'];
        }

        $config = array_merge($config, $this->_getAdditionalConfig());

        return $this->jsonEncoder->encode($config);
    }

}
