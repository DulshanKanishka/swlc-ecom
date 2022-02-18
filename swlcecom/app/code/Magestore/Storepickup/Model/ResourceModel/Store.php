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

namespace Magestore\Storepickup\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;

/**
 * Resource Model Store.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Store extends \Magestore\Storepickup\Model\ResourceModel\AbstractResource
{
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @var StoreUrlPathGeneratorInterface
     */
    protected $_storeUrlPathGenerator;

    /**
     * @var \Magento\UrlRewrite\Model\Storage\DbStorage
     */
    protected $_urlRewriteDbStorage;

    /**
     * Class constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param string|null                                  $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magestore\Storepickup\Model\StoreUrlPathGeneratorInterface $storeUrlPathGenerator,
        \Magento\UrlRewrite\Model\Storage\DbStorage $urlRewriteDbStorage,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
        $this->_jsonHelper = $jsonHelper;
        $this->_urlRewriteDbStorage = $urlRewriteDbStorage;
        $this->_storeUrlPathGenerator = $storeUrlPathGenerator;
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_STORE, 'storepickup_id');
    }

    /**
     * Retrieve select object for load object data.
     *
     * @param string                                 $field
     * @param mixed                                  $value
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return \Zend_Db_Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        /** @var \Zend_Db_Select $select */
        $select = parent::_getLoadSelect($field, $value, $object);
        $select->joinLeft(
            ['table_schedule' => $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_SCHEDULE)],
            $this->getMainTable() . '.schedule_id = table_schedule.schedule_id'
        )->joinLeft(
            ['table_image' => $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_IMAGE)],
            $this->getMainTable() . '.baseimage_id = table_image.image_id',
            ['baseimage' => 'path']
        );

        return $select;
    }

    /**
     * get tag ids of store.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    public function getTagIds(\Magento\Framework\Model\AbstractModel $object)
    {
        $connection = $this->getConnection();
        $id = (int) $object->getId();

        $select = $connection->select()->from(
            $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_STORE_TAG),
            'tag_id'
        )->where(
            $this->getIdFieldName() . ' = :object_id'
        );

        return $connection->fetchCol($select, [':object_id' => $id]);
    }

    /**
     * get holiday ids of store.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    public function getHolidayIds(\Magento\Framework\Model\AbstractModel $object)
    {
        $connection = $this->getConnection();
        $id = (int) $object->getId();

        $select = $connection->select()->from(
            $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_STORE_HOLIDAY),
            'holiday_id'
        )->where(
            $this->getIdFieldName() . ' = :object_id'
        );

        return $connection->fetchCol($select, [':object_id' => $id]);
    }

    /**
     * get holiday ids of store.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    public function getSpecialdayIds(\Magento\Framework\Model\AbstractModel $object)
    {
        $connection = $this->getConnection();
        $id = (int) $object->getId();

        $select = $connection->select()->from(
            $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_STORE_SPECIALDAY),
            'specialday_id'
        )->where(
            $this->getIdFieldName() . ' = :object_id'
        );

        return $connection->fetchCol($select, [':object_id' => $id]);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param array                                  $tagIds
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignTags(\Magento\Framework\Model\AbstractModel $object, array $tagIds = [])
    {
        $id = (int) $object->getId();
        $table = $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_STORE_TAG);

        $old = $this->getTagIds($object);
        $new = $tagIds;

        /*
         * Remove stores from object
         */
        $this->deleteData(
            $table,
            [
                $this->getIdFieldName() . ' = ?' => $id,
                'tag_id IN(?)' => array_values(array_diff($old, $new)),
            ]
        );

        /*
         * Add stores to object
         */
        $insert = [];
        foreach (array_values(array_diff($new, $old)) as $tagId) {
            $insert[] = [$this->getIdFieldName() => $id, 'tag_id' => (int) $tagId];
        }
        $this->insertData($table, $insert);

        return $this;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param array                                  $holidayIds
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignHolidays(\Magento\Framework\Model\AbstractModel $object, array $holidayIds = [])
    {
        $id = (int) $object->getId();
        $table = $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_STORE_HOLIDAY);

        $old = $this->getHolidayIds($object);
        $new = $holidayIds;

        /*
         * Remove stores from object
         */
        $this->deleteData(
            $table,
            [
                $this->getIdFieldName() . ' = ?' => $id,
                'holiday_id IN(?)' => array_values(array_diff($old, $new)),
            ]
        );

        /*
         * Add stores to object
         */
        $insert = [];
        foreach (array_values(array_diff($new, $old)) as $holidayId) {
            $insert[] = [$this->getIdFieldName() => $id, 'holiday_id' => (int) $holidayId];
        }
        $this->insertData($table, $insert);

        return $this;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param array                                  $specialdayIds
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignSpecialdays(\Magento\Framework\Model\AbstractModel $object, array $specialdayIds = [])
    {
        $id = (int) $object->getId();
        $table = $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_STORE_SPECIALDAY);

        $old = $this->getSpecialdayIds($object);
        $new = $specialdayIds;

        /*
         * Remove stores from object
         */
        $this->deleteData(
            $table,
            [
                $this->getIdFieldName() . ' = ?' => $id,
                'specialday_id IN(?)' => array_values(array_diff($old, $new)),
            ]
        );

        /*
         * Add stores to object
         */
        $insert = [];
        foreach (array_values(array_diff($new, $old)) as $holidayId) {
            $insert[] = [$this->getIdFieldName() => $id, 'specialday_id' => (int) $holidayId];
        }
        $this->insertData($table, $insert);

        return $this;
    }

    /**
     * Save image data for object.
     *
     * @param array $decodedImageJsonData
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveImagesData(\Magento\Framework\Model\AbstractModel $object, $decodedImageJsonData = [])
    {
        $table = $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_IMAGE);

        // Delete images which need to remove
        if (isset($decodedImageJsonData['deleteImages'])) {
            $this->deleteData($table, ['image_id IN(?)' => $decodedImageJsonData['deleteImages']]);
        }

        // Insert the new images
        if (isset($decodedImageJsonData['insertImages'])) {
            $this->insertData($table, $decodedImageJsonData['insertImages']);
        }

        // Make base image
        $baseimageId = (
            isset($decodedImageJsonData['baseImage'])
            && ($imageId = $this->_getImageIdByPath($decodedImageJsonData['baseImage']))
        ) ? $imageId : new \Zend_Db_Expr('NULL');

        $this->updateData(
            $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_STORE),
            ['baseimage_id' => $baseimageId],
            ['storepickup_id = ?' => $object->getId()]
        );

        return $this;
    }

    /**
     * Get image id by path.
     *
     * @param $path
     *
     * @return string
     */
    protected function _getImageIdByPath($path) {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_IMAGE),
            ['image_id']
        )->where(
            'path = :path'
        );

        return $connection->fetchOne(
            $select,
            [':path' => $path]
        );
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function countHolidays(\Magento\Framework\Model\AbstractModel $object)
    {
        $connection = $this->getConnection();
        $id = (int) $object->getId();

        $select = $connection->select()->from(
            ['store_holiday' => $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_STORE_HOLIDAY)],
            ['COUNT(*)']
        )->joinLeft(
            ['holiday' => $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_HOLIDAY)],
            'store_holiday.holiday_id = holiday.holiday_id'
        )->where('holiday.date_from <= holiday.date_to')
            ->where($this->getIdFieldName() . ' = :object_id');

        return $connection->fetchOne($select, [':object_id' => $id]);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function countSpecialdays(\Magento\Framework\Model\AbstractModel $object)
    {
        $connection = $this->getConnection();
        $id = (int) $object->getId();

        $select = $connection->select()->from(
            ['store_specialday' => $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_STORE_SPECIALDAY)],
            ['COUNT(*)']
        )->joinLeft(
            ['specialday' => $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_SPECIALDAY)],
            'store_specialday.specialday_id = specialday.specialday_id'
        )->where('specialday.date_from <= specialday.date_to')
            ->where($this->getIdFieldName() . ' = :object_id');

        return $connection->fetchOne($select, [':object_id' => $id]);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    public function deleteUrlRewrite(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->deleteData(
            $this->getTable('url_rewrite'),
            ['target_path IN(?)'=> [$this->_storeUrlPathGenerator->getCanonicalUrlPath($object)]]
        );

        return $this;
    }

}
