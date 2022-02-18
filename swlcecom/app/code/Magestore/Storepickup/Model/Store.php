<?php

/**
 * Magestore
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

use Magento\Framework\Exception\LocalizedException;
use Magestore\Storepickup\Model\Schedule\Option\WeekdayStatus;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Model Store.
 * @method \Magestore\Storepickup\Model\ResourceModel\Store _getResource()
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Store extends AbstractModel
{
    const MAX_COUNT_TIME_CHECK_URL_REWRITE = 100;

    const MARKER_ICON_RELATIVE_PATH = 'magestore/storepickup/images/store/marker';

    /**
     * number of seconds in a day.
     */
    const TIME_DAY = 86400;

    /**
     * method id getter.
     */
    const METHOD_GET_TAG_ID = 1;
    const METHOD_GET_HOLIDAY_ID = 2;
    const METHOD_GET_SPECIALDAY_ID = 3;
    const METHOD_GET_ORDER_ID = 4;

    const STOREPICUP_PENDING = 0;
    const STOREPICUP_PROCESSION = 1;
    const STOREPICUP_COMPLETED = 2;

    /**
     * mapping method builder.
     *
     * @var array
     */
    protected $_methodGetters = [
        self::METHOD_GET_TAG_ID        => 'getTagIds',
        self::METHOD_GET_HOLIDAY_ID    => 'getHolidayIds',
        self::METHOD_GET_SPECIALDAY_ID => 'getSpecialdayIds',
        self::METHOD_GET_ORDER_ID => 'getHolidayIds',
    ];

	/* @var $_storeId Support Multiple Store */

    protected $_storeId = null;
	
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;

    /**
     * @var \Magestore\Storepickup\Model\ResourceModel\Store\CollectionFactory
     */
    protected $_storeCollectionFactory;

    /**
     * @var \Magestore\Storepickup\Model\ResourceModel\Specialday\CollectionFactory
     */
    protected $_specialdayCollectionFactory;

    /**
     * @var \Magestore\Storepickup\Model\ResourceModel\Holiday\CollectionFactory
     */
    protected $_holidayCollectionFactory;

    /**
     * @var \Magestore\Storepickup\Model\ResourceModel\Image\CollectionFactory
     */
    protected $_imageCollectionFactory;

    /**
     * @var \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory
     */
    protected $_urlRewriteCollectionFactory;

    /**
     * @var \Magestore\Storepickup\Model\SystemConfig
     */
    protected $_systemConfig;

    /**
     * @var StoreUrlPathGeneratorInterface
     */
    protected $_storeUrlPathGenerator;

    /**
     * @var StoreUrlRewriteGeneratorInterface
     */
    protected $_storeUrlRewriteGenerator;

    /**
     * @var UrlPersistInterface
     */
    protected $_urlPersist;
	
	/**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

	/**
     * @var \Magestore\Storepickup\Helper\Url
     */
    protected $_storepickupHelperUrl;
	
	/**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
	
    /**
     * Store constructor.
     *
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Directory\Model\RegionFactory                       $regionFactory
     * @param \Magento\Directory\Model\CountryFactory                      $countryFactory
     * @param \Magento\Framework\Json\Helper\Data                          $jsonHelper
     * @param ResourceModel\Specialday\CollectionFactory                   $specialdayCollectionFactory
     * @param ResourceModel\Holiday\CollectionFactory                      $holidayCollectionFactory
     * @param ResourceModel\Image\CollectionFactory                        $imageCollectionFactory
     * @param SystemConfig                                                 $systemConfig
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|NULL $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|NULL           $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magestore\Storepickup\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory,
        \Magestore\Storepickup\Model\ResourceModel\Specialday\CollectionFactory $specialdayCollectionFactory,
        \Magestore\Storepickup\Model\ResourceModel\Holiday\CollectionFactory $holidayCollectionFactory,
        \Magestore\Storepickup\Model\ResourceModel\Image\CollectionFactory $imageCollectionFactory,
        \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory $urlRewriteCollectionFactory,
        \Magestore\Storepickup\Model\SystemConfig $systemConfig,
        StoreUrlPathGeneratorInterface $storeUrlPathGenerator,
        StoreUrlRewriteGeneratorInterface $storeUrlRewriteGenerator,
        UrlPersistInterface $urlPersist,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magestore\Storepickup\Helper\Url $storepickupHelperUrl,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = NULL,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = NULL,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_regionFactory = $regionFactory;
        $this->_jsonHelper = $jsonHelper;
        $this->_countryFactory = $countryFactory;

        $this->_storeCollectionFactory = $storeCollectionFactory;
        $this->_specialdayCollectionFactory = $specialdayCollectionFactory;
        $this->_holidayCollectionFactory = $holidayCollectionFactory;
        $this->_imageCollectionFactory = $imageCollectionFactory;
        $this->_urlRewriteCollectionFactory = $urlRewriteCollectionFactory;

        $this->_systemConfig = $systemConfig;
        $this->_storeUrlPathGenerator = $storeUrlPathGenerator;
        $this->_storeUrlRewriteGenerator = $storeUrlRewriteGenerator;
        $this->_urlPersist = $urlPersist;
		
		$this->_storeManager = $storeManager;
		$this->_storepickupHelperUrl = $storepickupHelperUrl;
		$this->_scopeConfig = $scopeConfig;
    }

    /**
     * Model construct that should be used for object initialization.
     */
    protected function _construct()
    {
        $this->_init('Magestore\Storepickup\Model\ResourceModel\Store');
    }

    /**
     * Processing object before save data.
     */
    public function beforeSave()
    {
        $this->_prepareStateValue()
            ->_prepareScheduleIdValue()
            ->_prepareRewriteRequestPath();

        return parent::beforeSave();
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    protected function _prepareRewriteRequestPath()
    {
        $urlKey = $this->getData('rewrite_request_path');
        if ($urlKey === '' || $urlKey === NULL) {
            $this->setData('rewrite_request_path', $this->_storeUrlPathGenerator->generateUrlKey($this));
        }

        /**
         * check exists rewrite request path with limit the time by MAX_COUNT_TIME_CHECK_URL_REWRITE
         */
        $checkNth = 0;
        while (
            $this->_checkExistsRewriteRequestPath($this->getData('rewrite_request_path'))
            && $checkNth++ < self::MAX_COUNT_TIME_CHECK_URL_REWRITE
        ) {
            $this->setData('rewrite_request_path', $this->getData('rewrite_request_path') . '-' . $this->getId());
        }

        if ($checkNth > self::MAX_COUNT_TIME_CHECK_URL_REWRITE) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The url key is already exists and system can not generate it automatic. You need to specified an other url key!')
            );
        }

        return $this;
    }

    /**
     * Check existing rewrite request path.
     *
     * @param string $rewriteRequestPath
     *
     * @return bool
     */
    protected function _checkExistsRewriteRequestPath($rewriteRequestPath)
    {
        /** @var \Magestore\Storepickup\Model\ResourceModel\Store\Collection $storeCollection */
        $storeCollection = $this->_storeCollectionFactory->create();
        $storeCollection->addFieldToFilter('storepickup_id', ['neq' => $this->getId()])
            ->addFieldToFilter('rewrite_request_path', $rewriteRequestPath);
        if($storeCollection->getSize()) {
            return true;
        }

        /** @var \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection $urlRewriteCollection */
        $urlRewriteCollection = $this->_urlRewriteCollectionFactory->create();
        $urlRewriteCollection->addFieldToFilter('request_path', $rewriteRequestPath)
            ->addFieldToFilter('target_path', ['neq' => $this->_storeUrlPathGenerator->getCanonicalUrlPath($this)]);
        return $urlRewriteCollection->getSize();
    }

    /**
     * prepare schedule value before save.
     *
     * @return $this
     */
    protected function _prepareScheduleIdValue()
    {
        if ($this->hasData('schedule_id') && !$this->getData('schedule_id')) {
            $this->setData('schedule_id', new \Zend_Db_Expr('NULL'));
        }

        return $this;
    }

    /**
     * prepare state value before save.
     *
     * @return $this
     */
    protected function _prepareStateValue()
    {
        if ($this->hasData('state_id')) {
            $region = $this->_regionFactory->create()->load($this->getData('state_id'));
            if ($region->getId()) {
                $this->setData('state', $region->getName());
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave()
    {
        parent::afterSave();

        $this->_saveSerializeData()
            ->_saveImageData()
            ->_makeUrlRewrite();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function afterDelete()
    {
        parent::afterDelete();

        $this->_getResource()->deleteUrlRewrite($this);

        return $this;
    }

    /**
     * Make url rewrite after save store.
     *
     * @return $this
     */
    protected function _makeUrlRewrite()
    {
        if ($this->dataHasChangedFor('rewrite_request_path')) {
            $urls = $this->_storeUrlRewriteGenerator->generate($this);
            $this->_urlPersist->replace($urls);
        }

        return $this;
    }

    /**
     * prepare data image.
     *
     * @return $this
     */
    protected function _saveImageData()
    {
        if ($this->hasData('arrayImages') && is_array($this->getData('arrayImages'))) {
            $this->_getResource()->saveImagesData(
                $this,
                $this->decodeImageJsonData($this->getData('arrayImages'))
            );
        }

        return $this;
    }

    /**
     * decode image json data.
     *
     * @param array $imagesJsonData
     *
     * @return array
     */
    public function decodeImageJsonData(array $imagesJsonData = [])
    {
        if (!$this->getId()) {
            return [];
        }

        $deleteImages = [];
        $baseImage = NULL;
        $insertImages = [];

        foreach ($imagesJsonData as $imageJson) {
            $imageData = $this->_jsonHelper->jsonDecode($imageJson);
            if (isset($imageData['image_id'])) {
                // the images are existed in database
                if (isset($imageData['remove'])) {
                    // the images need to remove
                    $deleteImages[] = $imageData['image_id'];
                } elseif (isset($imageData['base'])) {
                    // the image need to make is base image.
                    $baseImage = $imageData['file'];
                }
            } elseif (!isset($imageData['remove'])) {
                // the new images need to insert to database
                $path = \Magestore\Storepickup\Model\Image::IMAGE_GALLERY_PATH . $imageData['file'];
                $imageItem = [
                    'path'                  => $path,
                    'pickup_id' => $this->getId(),
                ];

                if (isset($imageData['base'])) {
                    $baseImage = $path;
                }

                $insertImages[] = $imageItem;
            }
        }

        return [
            'deleteImages' => $deleteImages,
            'baseImage'    => $baseImage,
            'insertImages' => $insertImages,
        ];
    }

    /**
     * Save serialize data.
     *
     * @return $this
     */
    protected function _saveSerializeData()
    {
        if ($this->hasData('in_tag_ids')) {
            $this->assignTags($this->getData('in_tag_ids'));
        }

        if ($this->hasData('in_holiday_ids')) {
            $this->assignHolidays($this->getData('in_holiday_ids'));
        }

        if ($this->hasData('in_specialday_ids')) {
            $this->assignSpecialdays($this->getData('in_specialday_ids'));
        }

        return $this;
    }

    /**
     * assign Tags to Store.
     *
     * @param array $tagIds
     */
    public function assignTags(array $tagIds = [])
    {
        $this->_getResource()->assignTags($this, $tagIds);

        return $this;
    }

    /**
     * assign Holidays to Store.
     *
     * @param array $holidayIds
     */
    public function assignHolidays(array $holidayIds = [])
    {
        $this->_getResource()->assignHolidays($this, $holidayIds);

        return $this;
    }

    /**
     * assign Specialdays to Store.
     *
     * @param array $specialdayIds
     */
    public function assignSpecialdays(array $specialdayIds = [])
    {
        $this->_getResource()->assignSpecialdays($this, $specialdayIds);

        return $this;
    }

    /**
     * get state id by name.
     *
     * @return mixed
     */
    public function getStateId()
    {
        $region = $this->_regionFactory->create()
            ->loadByName($this->getData('state'), $this->getData('country_id'));

        return $region->getId();
    }

    /**
     * Get Country Name by code.
     *
     * @return string
     */
    public function getCountryName()
    {
        $country = $this->_countryFactory->create()->loadByCode($this->getCountryId());

        return $country->getName();
    }

    /**
     * run build method.
     *
     * @param $methodId
     */
    public function runGetterMethod($methodId)
    {
        if (!isset($this->_methodGetters[$methodId])) {
            throw new LocalizedException(__('Method of %1 is not exists !', get_class($this)));
        }

        $getterMethod = $this->_methodGetters[$methodId];

        return $this->$getterMethod();
    }

    /**
     * get tag ids in Store.
     */
    public function getTagIds()
    {
        return $this->_getResource()->getTagIds($this);
    }

    /**
     * Load base image of store.
     *
     * @return $this
     *
     * @throws LocalizedException
     */
    public function loadBaseImage()
    {
        $this->_getResource()->loadBaseImage($this);

        return $this;
    }

    /**
     * get specialday data.
     *
     * @return array
     */
    public function getSpecialdaysData()
    {
        /** @var \Magestore\Storepickup\Model\ResourceModel\Specialday\Collection $collection */
        $collection = $this->_filterDays($this->getSpecialdays());

        $days = [];
        $key = 0;
        $timeDay = self::TIME_DAY;

        foreach ($collection as $item) {
            $days[$key]['name'] = $item->getSpecialdayName();
            $days[$key]['time_open'] = $item->getTimeOpen();
            $days[$key]['time_close'] = $item->getTimeClose();
            $dateFrom = strtotime($item->getDateFrom());
            $dateTo = strtotime($item->getDateTo());

            while ($dateFrom <= $dateTo) {
                $days[$key]['date'][] = date('Y-m-d', $dateFrom);
                $dateFrom += $timeDay;
            }

            ++$key;
        }

        return $days;
    }

    /**
     * filter specialdays, holidays.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     */
    protected function _filterDays(\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection)
    {
        $dayShow = $this->_systemConfig->getLimitStoreDays();
        $dateStart = date('Y-m-d');
        $dateEnd = date('Y-m-d', strtotime(date('Y-m-d')) + $dayShow * self::TIME_DAY);

        $collection->getSelect()->where('date_from <= date_to');
        $collection->addFieldToFilter('date_to', ['gteq' => $dateStart])
            ->addFieldToFilter('date_from', ['lteq' => $dateEnd]);

        return $collection;
    }

    /**
     * Get Specialday Collection  of Store.
     *
     * @return \Magestore\Storepickup\Model\ResourceModel\Specialday\Collection
     */
    public function getSpecialdays()
    {
        /** @var \Magestore\Storepickup\Model\ResourceModel\Specialday\Collection $collection */
        $collection = $this->_specialdayCollectionFactory->create();
        $collection->addFieldToFilter('specialday_id', ['in' => $this->getSpecialdayIds()]);

        return $collection;
    }

    /**
     * Get holiday ids of store.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    public function getSpecialdayIds()
    {
        return $this->_getResource()->getSpecialdayIds($this);
    }

    /**
     * get holidays data.
     *
     * @return array
     */
    public function getHolidaysData()
    {
        /** @var \Magestore\Storepickup\Model\ResourceModel\Holiday\Collection $collection */
        $collection = $this->_filterDays($this->getHolidays());

        $days = [];
        $key = 0;
        $timeDay = self::TIME_DAY;

        foreach ($collection as $item) {
            $days[$key]['name'] = $item->getHolidayName();
            $dateFrom = strtotime($item->getDateFrom());
            $dateTo = strtotime($item->getDateTo());

            while ($dateFrom <= $dateTo) {
                $days[$key]['date'][] = date('Y-m-d', $dateFrom);
                $dateFrom += $timeDay;
            }

            ++$key;
        }

        return $days;
    }

    /**
     * Get Holiday collection of Store.
     *
     * @return \Magestore\Storepickup\Model\ResourceModel\Specialday\Collection
     */
    public function getHolidays()
    {
        /** @var \Magestore\Storepickup\Model\ResourceModel\Holiday\Collection $collection */
        $collection = $this->_holidayCollectionFactory->create();
        $collection->addFieldToFilter('holiday_id', ['in' => $this->getHolidayIds()]);

        return $collection;
    }

    /**
     * get holiday ids of store.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    public function getHolidayIds()
    {
        return $this->_getResource()->getHolidayIds($this);
    }

    /**
     * @param $day
     *
     * @return bool
     */
    public function hasBreakTime($day)
    {
        return $this->isOpenday($day)
        && $this->getData($day . '_open') < $this->getData($day . '_open_break')
        && $this->getData($day . '_open_break') < $this->getData($day . '_close_break')
        && $this->getData($day . '_close_break') < $this->getData($day . '_close');
    }

    /**
     * Check store is open in a day.
     *
     * @param $day
     *
     * @return bool
     */
    public function isOpenday($day)
    {
        return $this->getScheduleId()
        && $this->isEnabled()
        && $this->getData($day . '_status') == WeekdayStatus::WEEKDAY_STATUS_OPEN
        && $this->getData($day . '_open') < $this->getData($day . '_close');
    }

    /**
     * check store is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getStatus() == \Magestore\Storepickup\Model\Status::STATUS_ENABLED ? TRUE : FALSE;
    }

    /**
     * check store has holiday.
     *
     * @return int
     */
    public function countHolidays()
    {
        return $this->_getResource()->countHolidays($this);
    }

    /**
     * check store has specialday.
     *
     * @return int
     */
    public function countSpecialdays()
    {
        return $this->_getResource()->countSpecialdays($this);
    }

    /**
     * Get first Image of store.
     *
     * @return \Magento\Framework\DataObject
     */
    public function getFirstImage()
    {
        return $this->getImages()->setPageSize(1)->setCurPage(1)->getFirstItem();
    }

    /**
     * Get Image collection of store.
     *
     * @return \Magestore\Storepickup\Model\ResourceModel\Image\Collection
     */
    public function getImages()
    {
        /** @var  \Magestore\Storepickup\Model\ResourceModel\Image\Collection $collection */
        $collection = $this->_imageCollectionFactory->create();
        $collection->addFieldToFilter('pickup_id', $this->getId());

        return $collection;
    }

    /**
     * Get meta title.
     *
     * @return mixed
     */
    public function getMetaTitle()
    {
        return $this->getData('meta_title') ? $this->getData('meta_title') : $this->getStoreName();
    }

    /**
     * Get meta description.
     *
     * @return mixed
     */
    public function getMetaDescription()
    {
        return $this->getData('meta_description') ? $this->getData('meta_description') : $this->getStoreName();
    }

    /**
     * Get meta keywords.
     *
     * @return mixed
     */
    public function getMetaKeywords()
    {
        return $this->getData('meta_keywords') ? $this->getData('meta_keywords') : $this->getStoreName();
    }
	
	public function import() 
	{
        $data = $this->getData();

        //prepare status
        $data['status'] = 1;
        //check exited store
        $collection = $this->getCollection()
                ->addFieldToFilter('store_name', $data['store_name'])
                ->addFieldToFilter('address', $data['address']);


        if (count($collection))
            return false;

        if (!isset($data['store_name']) || $data['store_name'] == '')
            return false;
        if (!isset($data['address']) || $data['address'] == '')
            return false;
        if (!isset($data['city']) || $data['city'] == '')
            return false;
        if (!isset($data['country_id']) || $data['country_id'] == '')
            return false;
        if (!isset($data['zipcode']) || $data['zipcode'] == '')
            return false;

        $storeName = strtolower(trim($data['store_name'], ' '));

        $storeName = $this->_storepickupHelperUrl->characterSpecial($storeName);
        $check = 1;
        while ($check == 0) {
            $stores = $this->getCollection()
                    ->addFieldToFilter('url_id_path', $storeName)
                    ->getFirstItem();

            if ($stores->getId()) {
                $storeName = $storeName . '-1';
            } else {
                $check = 0;
            }
        }

        $data['url_id_path'] = $storeName;

        $this->setData($data);	
		$this->save();

        $allstores = $this->_storeManager->getStores();
        foreach ($allstores as $store) {	
			$this->setStoreId($store->getStoreId())
				->updateUrlKey();				
        }
		
        return $this->getId();
    }
	
	public function setStoreId($value) {
        $this->_storeId = $value;
        return $this;
    }

    public function getStoreId() {
        return $this->_storeId;
    }
	
	public function updateUrlKey($rewriteRequestPath = '') {
        $id = $this->getId();
        $storeId = $this->_storeId;
        if (!$storeId) {
            $storeId = 0;
        }

        $url_key = $rewriteRequestPath ? $rewriteRequestPath : $this->getData('url_id_path');
        $url_suffix = $this->_scopeConfig->getValue(
			'catalog/seo/product_url_suffix',
			\Magento\Store\Model\ScopeInterface::SCOPE_STORE,
			$this->_storeManager->getStore()->getStoreId()
		);
		$urlrewrite = $this->loadByIdpath($url_key, $storeId);

		// $urlrewrite->setData('id_path', $url_key);
		$urlrewrite->setData('request_path','storepickup/'. $url_key.$url_suffix);
		$urlrewrite->setData('target_path', 'storepickup/index/index/viewstore/' . $id);
		$urlrewrite->setData('store_id', $storeId);
		
        try {
           $urlrewrite->save();
        } catch (Exception $e) {
			
        }
    }
	
	public function loadByIdpath($idPath, $storeId) {
		$model = $this->_urlRewriteCollectionFactory->create()
				// ->addFieldToFilter('id_path', $idPath)
				->addFieldToFilter('store_id', $storeId)
				->getFirstItem();
        
        return $model;
    }
}
