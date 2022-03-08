<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Dulshan\Custom\Helper;

use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Image;

/**
 * Class Data
 * Helper class for getting options
 * @api
 * @since 100.0.2
 */
class Data extends \Magento\ConfigurableProduct\Helper\Data
{
    private $imageUrlBuilder;
   public function __construct(
       ImageHelper $imageHelper, UrlBuilder $urlBuilder = null
   )
   {
       $this->imageUrlBuilder = $urlBuilder ?? ObjectManager::getInstance()->get(UrlBuilder::class);
       parent::__construct($imageHelper, $urlBuilder);
   }

    public function getOptions($currentProduct, $allowedProducts)
    {
        $options = [];
        $allowAttributes = $this->getAllowAttributes($currentProduct);

        foreach ($allowedProducts as $product) {
            $productId = $product->getId();
            $salables = $product->getIsSalable();
            foreach ($allowAttributes as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $productAttributeId = $productAttribute->getId();
                $attributeValue = $product->getData($productAttribute->getAttributeCode());

                $options[$productAttributeId][$attributeValue][] = $productId;
                $options['index'][$productId][$productAttributeId] = $attributeValue;
                $options['salables'][$productId][$salables] = $salables;

            }
        }
        return $options;
    }

}
