<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Preorder
 */


namespace Amasty\Preorder\Plugin\Catalog\Block\Product;

use Magento\Catalog\Block\Product\AbstractProduct as NativeAbstractProduct;
use Amasty\Preorder\Helper\Data;

class AbstractProduct
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var array
     */
    private $applicableBlocks = [
        'product.info.configurable',
        'product.info.simple',
        'product.info.bundle',
        'product.info.virtual',
        'product.info.downloadable',
        'product.info.grouped.stock'
    ];

    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param NativeAbstractProduct $subject
     * @param string $html
     *
     * @return string
     */
    public function afterToHtml(
        NativeAbstractProduct $subject,
        $html
    ) {
        if (in_array($subject->getNameInLayout(), $this->applicableBlocks)
            && $this->helper->preordersEnabled()
            && $this->helper->getIsProductPreorder($subject->getProduct())
        ) {
            $preorderNote = $this->helper->getProductPreorderNote($subject->getProduct());
            if ($preorderNote) {
                $html = '<div class="stock"><span>' . $preorderNote . '</span></div>';
            }
        }

        return $html;
    }
}
