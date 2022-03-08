<?php

namespace Dulshan\Custom\Block\Checkout;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

class DirectoryDataProcessor extends \Magento\Checkout\Block\Checkout\DirectoryDataProcessor
{
    private $countryOptions;
    private $regionCollectionFactory;
    private $countryCollectionFactory;
    private $storeManager;
    private $directoryHelper;
    private $regionOptions;

    public function __construct(
        CollectionFactory $countryCollection,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollection,
        StoreResolverInterface $storeResolver,
        DirectoryHelper $directoryHelper,
        StoreManagerInterface $storeManager = null
    ) {
        $this->countryCollectionFactory = $countryCollection;
        $this->regionCollectionFactory = $regionCollection;
        $this->directoryHelper = $directoryHelper;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
        parent::__construct($countryCollection, $regionCollection, $storeResolver, $directoryHelper, $storeManager);
    }

    public function process($jsLayout)
    {
        if (!isset($jsLayout['components']['checkoutProvider']['dictionaries'])) {
            $jsLayout['components']['checkoutProvider']['dictionaries'] = [
                'country_id' => $this->getCountryOptions(),
                'region_id' => $this->getRegionOptions(),
            ];
        }

        return $jsLayout;
    }

    private function getCountryOptions()
    {
        if (!isset($this->countryOptions)) {
            $this->countryOptions = $this->countryCollectionFactory->create()->loadByStore(
                $this->storeManager->getStore()->getId()
            )->toOptionArray();
            $this->countryOptions = $this->orderCountryOptions($this->countryOptions);
        }

        return $this->countryOptions;
    }

    private function orderCountryOptions(array $countryOptions)
    {
        $topCountryCodes = $this->directoryHelper->getTopCountryCodes();
        if (empty($topCountryCodes)) {
            return $countryOptions;
        }

        $headOptions = [];
        $tailOptions = [];

        foreach ($countryOptions as $countryOption) {
            if (empty($countryOption['value']) || in_array($countryOption['value'], $topCountryCodes)) {
                $headOptions[] = $countryOption;
            } else {
                $tailOptions[] = $countryOption;
            }
        }
        return array_merge($headOptions, $tailOptions);
    }

    private function getRegionOptions()
    {
        if (!isset($this->regionOptions)) {
            $this->regionOptions = $this->regionCollectionFactory->create()->addAllowedCountriesFilter(
                $this->storeManager->getStore()->getId()
            )->toOptionArray();
        }

        return $this->regionOptions;
    }
}
