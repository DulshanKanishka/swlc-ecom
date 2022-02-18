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

namespace Magestore\Storepickup\Model\ResourceModel;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
abstract class AbstractDbManageStores extends \Magestore\Storepickup\Model\ResourceModel\AbstractResource implements \Magestore\Storepickup\Model\ResourceModel\DbManageStoresInterface
{
    /**
     * @var \Magestore\Storepickup\Model\ResourceModel\Store\CollectionFactory
     */
    protected $_storeCollectionFactory;

    /**
     * Class constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param string|null                                  $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magestore\Storepickup\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
        $this->_storeCollectionFactory = $storeCollectionFactory;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param array                                  $storepickupIds
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function pickStores(\Magento\Framework\Model\AbstractModel $object, array $storepickupIds = [])
    {
        $id = (int) $object->getId();
        $table = $this->getStoreRelationTable();

        $old = $this->getStorepickupIds($object);
        $new = $storepickupIds;

        /*
         * remove stores from object
         */
        $this->deleteData(
            $table,
            [
                $this->getIdFieldName() . ' = ?' => $id,
                'storepickup_id IN(?)' => array_values(array_diff($old, $new)),
            ]
        );

        /*
         * add store to object
         */
        $insert = [];
        foreach (array_values(array_diff($new, $old)) as $storepickupId) {
            $insert[] = [$this->getIdFieldName() => $id, 'storepickup_id' => (int) $storepickupId];
        }
        $this->insertData($table, $insert);

        return $this;
    }

    /**
     * get collection store of model.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getStores(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Magestore\Storepickup\Model\ResourceModel\Store\Collection $collection */
        $collection = $this->_storeCollectionFactory->create();
        $collection->addFieldToFilter(
            'storepickup_id',
            ['in' => $this->getStorepickupIds($object)]
        );

        return $collection;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return mixed
     */
    public function getStorepickupIds(\Magento\Framework\Model\AbstractModel $object)
    {
        $connection = $this->getConnection();
        $id = (int) $object->getId();

        $select = $connection->select()->from(
            $this->getStoreRelationTable(),
            'storepickup_id'
        )->where(
            $this->getIdFieldName() . ' = :object_id'
        );

        return $connection->fetchCol($select, [':object_id' => $id]);
    }

    /**
     * get table relation ship.
     *
     * @return string
     */
    abstract public function getStoreRelationTable();
}
