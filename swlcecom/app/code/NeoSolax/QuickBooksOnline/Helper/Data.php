<?php

namespace NeoSolax\QuickBooksOnline\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Tax\Model\Calculation\Rate;

class Data extends AbstractHelper
{
    public function __construct(
        Rate $taxModelConfig,
        Context $context
    ) {
        $this->taxModelConfig = $taxModelConfig;
        parent::__construct($context);
    }

    public function getExcludeAmount($Amount, $taxRate)
    {
        $excludeAmount = $Amount - ($Amount * $taxRate / (100 + $taxRate));
        return $excludeAmount;
    }

}
