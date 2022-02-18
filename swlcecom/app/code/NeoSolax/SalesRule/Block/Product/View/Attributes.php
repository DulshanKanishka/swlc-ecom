<?php
namespace NeoSolax\SalesRule\Block\Product\View;

class Attributes extends \Magento\Catalog\Block\Product\View\Attributes
{
    public function getAdditionalData(array $excludeAttr = [])
    {
        $data = [];
        $product = $this->getProduct();
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
            if ($this->isVisibleOnFrontend($attribute, $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($product);

                if ($value instanceof Phrase) {
                    $value = (string)$value;
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = $this->priceCurrency->convertAndFormat($value);
                }
                if ($value == false) {
                    if ($attribute->getFrontendInput() == 'boolean')
                    {
                        if($product->getData($attribute->getAttributeCode()) == 0)
                        {
                            $value = 'N/A';
                        }
                        else{
                            $value = 'YES';
                        }
                    }
                    else
                    {
                        $value = 'N/A';
                    }
                }


                if (is_string($value) && strlen(trim($value))) {
                    $data[$attribute->getAttributeCode()] = [
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code' => $attribute->getAttributeCode(),
                    ];
                }
            }
        }
        return $data;
    }
}
