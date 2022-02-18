<?php
namespace Magenest\Xero\Block\Adminhtml\Xero;

use Magenest\Xero\Model\Config\TrackingCategory\TrackingCategory;
use Magento\Backend\Block\Template\Context;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Class Payment
 * @package Magenest\Xero\Block\Adminhtml\Xero
 */
class StoreView extends \Magento\Backend\Block\Widget
{
    const XML_PATH_STORE_VIEW_MAPPING_PREFIX = 'magenest_xero_config/xero_mapping/storeview_';
    protected $trackingCategory;
    protected $storeRepository;
    protected $storeViews = null;

    /**
     * StoreView constructor.
     * @param Context $context
     * @param TrackingCategory $trackingCategory
     * @param StoreRepositoryInterface $storeRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        TrackingCategory $trackingCategory,
        StoreRepositoryInterface $storeRepository,
        array $data = []
    ) {
        $this->trackingCategory = $trackingCategory;
        $this->storeRepository = $storeRepository;
        parent::__construct($context, $data);
    }

    public function getStoreViews()
    {
        if (!$this->storeViews) {
            $this->storeViews = $this->storeRepository->getList();
        }
        return $this->storeViews;
    }

    public function getTrackingCategories()
    {
        return $this->trackingCategory->toOptionArray();
    }

    public function getSavedMapping($storeId)
    {
        return $this->_scopeConfig->getValue(self::XML_PATH_STORE_VIEW_MAPPING_PREFIX.$storeId);
    }
}
