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

namespace Magestore\Storepickup\Model;

use Magento\Store\Model\ScopeInterface;
use Magestore\Storepickup\Model\Config\Source\StoreSearchCriteria;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class SystemConfig
{
    /**
     * @var array
     */
    protected $_distanceUnitMap = [
        \Magestore\Storepickup\Model\Config\Source\Unit::UNIT_KILOMETER => 'Km',
        \Magestore\Storepickup\Model\Config\Source\Unit::UNIT_MILES => 'Mi',

    ];

    /**
     * Config path to Enable/Disable extension in frontend.
     */
    const XML_PATH_GENERAL_ENABLE_FRONTEND = 'storepickup/general/enable_frontend';

    /**
     * Config path to show for not show top link in frontend.
     */
    const XML_PATH_GENERAL_DISPLAY_TOPLINK = 'storepickup/general/display_toplink';

    /**
     * Config path to set sort order type of list store.
     */
    const XML_PATH_GENERAL_ORDER_TYPE = 'storepickup/general/order_type';

    /**
     * Config path to set page title in list store.
     */
    const XML_PATH_GENERAL_PAGE_TITLE = 'storepickup/general/page_title';

    /**
     * Config path to limit Store's holidays
     * and special days within this period will be shown in frontend.
     */
    const XML_PATH_GENERAL_LIMIT_DAY = 'storepickup/general/limit_day';

    /**
     * Config path to limit numer of image gallery.
     */
    const XML_PATH_GENERAL_LIMIT_IMAGE_GALLERY = 'storepickup/general/limit_image_gallery';

    /**
     * Config path to limit the number of stores will be show in list store when paging at frontend.
     */
    const XML_PATH_GENERAL_LIST_STORE_PAGE_SIZE = 'storepickup/general/list_store_page_size';

    /**
     * Config path to set Google API key.
     */
    const XML_PATH_SERVICE_GOOGLE_API_KEY = 'storepickup/service/google_api_key';

    /**
     * Config path to allow customer comment Facebook.
     */
    const XML_PATH_SERVICE_ALLOW_FACEBOOK_COMMENT = 'storepickup/service/allow_facebook_comment';

    /**
     * Config path to set Facebook API key.
     */
    const XML_PATH_SERVICE_FACEBOOK_API_KEY = 'storepickup/service/facebook_api_key';

    /**
     * Config path to set local language Facebook Comment.
     */
    const XML_PATH_SERVICE_LANGUAGE_FACEBOOK = 'storepickup/service/language_facebook';

    /**
     * Config path to set criteria search store in frontend.
     */
    const XML_PATH_SEARCHING_SEARCH_CRITERIA = 'storepickup/searching/search_criteria';

    /**
     * Config path to set default radius.
     */
    const XML_PATH_SEARCHING_DEFAULT_RADIUS = 'storepickup/searching/default_radius';

    /**
     * Config path to set unit search in frontend.
     */
    const XML_PATH_SEARCHING_DISTANCE_UNIT = 'storepickup/searching/distance_unit';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * Config path to get title of Storepikcup Shipping Method
     */
    const XML_PATH_CARRIERS_TITLE = 'carriers/storepickup/title';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * Config path to get title of Storepikcup Shipping Method
     */
    const XML_PATH_CARRIERS_NAME = 'carriers/storepickup/name';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * Config path to get name of Storepikcup Shipping Method
     */
    const XML_PATH_CARRIERS_PRICE = 'carriers/storepickup/price';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * Config path to get default store to pickup
     */
    const XML_PATH_CARRIERS_DEFAULT_STORE = 'carriers/storepickup/default_store';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * Config path to enable pickup date/time
     */
    const XML_PATH_CARRIERS_DISPLAY_PICKUPTIME = 'carriers/storepickup/display_pickuptime';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * Config path to get time interval
     */
    const XML_PATH_CARRIERS_TIME_INTERVAL = 'carriers/storepickup/time_interval';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * Config path to get specificerr message
     */
    const XML_PATH_CARRIERS_SPECIFICERMSG = 'carriers/storepickup/specificerrmsg';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * Config path to get sort order
     */
    const XML_PATH_CARRIERS_SORT_ORDER = 'carriers/storepickup/sort_order';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */

    protected $_scopeConfig;

    /**
     * SystemConfig constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Check enable frontend.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function isEnableFrontend($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_GENERAL_ENABLE_FRONTEND,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check show top link.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function isShowTopLink($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_GENERAL_DISPLAY_TOPLINK,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * get sort store type.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function getSortStoreType($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_GENERAL_ORDER_TYPE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get page tilte.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function getPageTitpe($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_GENERAL_PAGE_TITLE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get limit store day.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function getLimitStoreDays($store = null)
    {
        return (int) $this->_scopeConfig->getValue(
            self::XML_PATH_GENERAL_LIMIT_DAY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get max numer image gallery.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function getMaxImageGallery($store = null)
    {
        return (int) $this->_scopeConfig->getValue(
            self::XML_PATH_GENERAL_LIMIT_IMAGE_GALLERY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Limit the number of stores will be show in list store when paging at frontend.
     *
     * @param null $store
     *
     * @return int
     */
    public function getPainationSize($store = null)
    {
        return (int) $this->_scopeConfig->getValue(
            self::XML_PATH_GENERAL_LIST_STORE_PAGE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get google api key.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function getGoolgeApiKey($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_SERVICE_GOOGLE_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check allow customer use facebook comment.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function isAllowFacebookComment($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_SERVICE_ALLOW_FACEBOOK_COMMENT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Facebook Api key.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function getFacebookApiKey($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_SERVICE_FACEBOOK_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get local Facebook language.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function getLocaleFacebookLanquage($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_SERVICE_LANGUAGE_FACEBOOK,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get search criteria store in list store frontend.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function getSearchCriteria($store = null)
    {
        return explode(
            ',',
            $this->_scopeConfig->getValue(
                self::XML_PATH_SEARCHING_SEARCH_CRITERIA,
                ScopeInterface::SCOPE_STORE,
                $store
            )
        );
    }

    /**
     * @return bool
     */
    public function isShowTabSearchByArea()
    {
        $searchCriteria = $this->getSearchCriteria();

        return !in_array(StoreSearchCriteria::SEARCH_CRITERIA_NONE, $searchCriteria) && count($searchCriteria);
    }

    /**
     * @return bool
     */
    public function hasSearchByStoreName()
    {
        return in_array(StoreSearchCriteria::SEARCH_CRITERIA_STORE_NAME, $this->getSearchCriteria());
    }

    /**
     * @return bool
     */
    public function hasSearchByCountry()
    {
        return in_array(StoreSearchCriteria::SEARCH_CRITERIA_COUNTRY, $this->getSearchCriteria());
    }

    /**
     * @return bool
     */
    public function hasSearchByState()
    {
        return in_array(StoreSearchCriteria::SEARCH_CRITERIA_STATE, $this->getSearchCriteria());
    }

    /**
     * @return bool
     */
    public function hasSearchByCity()
    {
        return in_array(StoreSearchCriteria::SEARCH_CRITERIA_CITY, $this->getSearchCriteria());
    }

    /**
     * @return bool
     */
    public function hasSearchByZipcode()
    {
        return in_array(StoreSearchCriteria::SEARCH_CRITERIA_ZIPCODE, $this->getSearchCriteria());
    }

    /**
     * Get default radius.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function getDefaultRadius($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_SEARCHING_DEFAULT_RADIUS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get distance unit.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function getDistanceUnit($store = null)
    {
        $unitCode = $this->_scopeConfig->getValue(
            self::XML_PATH_SEARCHING_DISTANCE_UNIT,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return isset($this->_distanceUnitMap[$unitCode]) ? $this->_distanceUnitMap[$unitCode] : 'Km';
    }
    /**
     * Get Title of Store Pikcup method.
     *
     * @param \Magento\Store\Model\Store|string|int|null $store
     *
     * @return mixed
     */
    public function getShippingTitle($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_CARRIERS_TITLE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    public function getShippingName($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_CARRIERS_NAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    public function getPrice($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_CARRIERS_PRICE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    public function getDefaultStore($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_CARRIERS_DEFAULT_STORE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    public function isDisplayPickuptime($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_CARRIERS_DISPLAY_PICKUPTIME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    public function getTimeIntercval($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_CARRIERS_TIME_INTERVAL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    public function getErrorMessage($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_CARRIERS_SPECIFICERMSG,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    public function getSortOrder($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_CARRIERS_SORT_ORDER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
