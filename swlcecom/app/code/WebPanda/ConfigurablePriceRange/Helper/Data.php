<?php
namespace WebPanda\ConfigurablePriceRange\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Class Data
 * @package WebPanda\ConfigurablePriceRange\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface
     */
    private $configurableOptionsProvider;

    /**
     * Data constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->scopeConfig = $context->getScopeConfig();
    }

    /**
     * Check if is enabled
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag('catalog/pricerange/enabled', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if display label
     * @return bool
     */
    public function displayLabels()
    {
        return $this->scopeConfig->isSetFlag('catalog/pricerange/display_labels', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get min final amount
     *
     * @param $product
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    public function getMinFinalAmount($product)
    {
        $minAmount = null;
        foreach ($this->getUsedProducts($product) as $child) {
            $childPriceAmount = $child->getPriceInfo()->getPrice('final_price')->getAmount();
            if (!$minAmount || ($childPriceAmount->getValue() < $minAmount->getValue())) {
                $minAmount = $childPriceAmount;
            }
        }

        return $minAmount;
    }

    /**
     * Get max final amount
     *
     * @param $product
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    public function getMaxFinalAmount($product)
    {
        $maxAmount = null;
        foreach ($this->getUsedProducts($product) as $child) {
            $childPriceAmount = $child->getPriceInfo()->getPrice('final_price')->getAmount();
            if (!$maxAmount || ($childPriceAmount->getValue() > $maxAmount->getValue())) {
                $maxAmount = $childPriceAmount;
            }
        }

        return $maxAmount;
    }

    /**
     * Get children simple products
     *
     * @param $product
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    private function getUsedProducts($product)
    {
        return $this->getConfigurableOptionsProvider()->getProducts($product);
    }

    /**
     * @return \Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface|mixed
     */
    private function getConfigurableOptionsProvider()
    {
        if (null === $this->configurableOptionsProvider) {
            $this->configurableOptionsProvider = ObjectManager::getInstance()
                ->get(\Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface::class);
        }
        return $this->configurableOptionsProvider;
    }
}
