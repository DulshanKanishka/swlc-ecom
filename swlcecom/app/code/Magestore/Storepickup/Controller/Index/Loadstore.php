<?php

/**
 * Magestore.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Controller\Index;

use Magento\Framework\Controller\ResultFactory;
use Magestore\Storepickup\Model\Config\Source\OrderTypeStore;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Loadstore extends \Magestore\Storepickup\Controller\Index
{
    /**
     * Default current page.
     */
    const DEFAULT_CURRENT_PAGINATION = 1;

    /**
     * Default range pagination.
     */
    const DEFAULT_RANGE_PAGINATION = 5;

    /**
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        /** @var \Magestore\Storepickup\Model\ResourceModel\Store\Collection $collection */
        $collection = $this->_filterStoreCollection($this->_storeCollectionFactory->create());

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $pager = $resultPage->getLayout()->createBlock(
            'Magestore\Storepickup\Block\ListStore\Pagination',
            'storepickup.pager',
            [
                'collection' => $collection,
                'data' => ['range' => self::DEFAULT_RANGE_PAGINATION],
            ]
        );

        /** @var \Magento\Framework\Controller\Result\Raw $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setHeader('Content-type', 'text/plain');

        $response->setContents(
            $this->_jsonHelper->jsonEncode(
                [
                    'storesjson' => $collection->prepareJson(),
                    'pagination' => $pager->toHtml(),
                    'num_store' => $collection->getSize(),
                ]
            )
        );

        return $response;
    }

    /**
     * filter store.
     *
     * @param \Magestore\Storepickup\Model\ResourceModel\Store\Collection $collection
     *
     * @return \Magestore\Storepickup\Model\ResourceModel\Store\Collection
     */
    protected function _filterStoreCollection(
        \Magestore\Storepickup\Model\ResourceModel\Store\Collection $collection
    ) {
        $collection->addFieldToSelect([
            'store_name',
            'phone',
            'address',
            'latitude',
            'longitude',
            'marker_icon',
            'zoom_level',
            'rewrite_request_path',
        ]);

        $curPage = $this->getRequest()->getParam('curPage', self::DEFAULT_CURRENT_PAGINATION);
        $collection->setPageSize($this->_systemConfig->getPainationSize())->setCurPage($curPage);

        /*
         * Filter store enabled
         */
        $collection->addFieldToFilter('status', \Magestore\Storepickup\Model\Status::STATUS_ENABLED);

        /*
         * filter by radius
         */
        if ($radius = $this->getRequest()->getParam('radius')) {
            $latitude = $this->getRequest()->getParam('latitude');
            $longitude = $this->getRequest()->getParam('longitude');
            $collection->addLatLngToFilterDistance($latitude, $longitude, $radius);
        }

        /*
         * filter by tags
         */
        $tagIds = $this->getRequest()->getParam('tagIds');
        if (!empty($tagIds)) {
            $collection->addTagsToFilter($tagIds);
        }

        /*
         * filter by store information
         */

        if ($countryId = $this->getRequest()->getParam('countryId')) {
            $collection->addFieldToFilter('country_id', $countryId);
        }

        if ($storeName = $this->getRequest()->getParam('storeName')) {
            $collection->addFieldToFilter('store_name', ['like' => "%$storeName%"]);
        }

        if ($state = $this->getRequest()->getParam('state')) {
            $collection->addFieldToFilter('state', ['like' => "%$state%"]);
        }

        if ($city = $this->getRequest()->getParam('city')) {
            $collection->addFieldToFilter('city', ['like' => "%$city%"]);
        }

        if ($zipcode = $this->getRequest()->getParam('zipcode')) {
            $collection->addFieldToFilter('zipcode', ['like' => "%$zipcode%"]);
        }

        // Set sort type for list store
        switch ($this->_systemConfig->getSortStoreType()) {
            case OrderTypeStore::SORT_BY_ALPHABETICAL:
                $collection->setOrder('store_name', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC);
                break;

            case OrderTypeStore::SORT_BY_DISTANCE:
                if ($radius) {
                    $collection->setOrder('distance', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC);
                }
                break;
            default:
                $collection->setOrder('sort_order', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC);
        }

        // Allow load base image for each store
        $collection->setLoadBaseImage(true);

        return $collection;
    }
}
