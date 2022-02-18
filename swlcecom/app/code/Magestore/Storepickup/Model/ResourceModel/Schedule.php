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

/**
 * Resource Model Schedule.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Schedule extends \Magestore\Storepickup\Model\ResourceModel\AbstractResource implements \Magestore\Storepickup\Model\ResourceModel\DbManageStoresInterface
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
     * {@inheritdoc}
     */
    public function _construct()
    {
        $this->_init(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_SCHEDULE, 'schedule_id');
    }

    /**
     * pick stores for model.
     *
     * @param array $storepickupIds
     *
     * @return mixed
     */
    public function pickStores(\Magento\Framework\Model\AbstractModel $object, array $storepickupIds = [])
    {
        $id = (int) $object->getId();

        $table = $this->getTable(\Magestore\Storepickup\Setup\InstallSchema::SCHEMA_STORE);
        $old = $this->getStorepickupIds($object);
        $new = $storepickupIds;

        /*
         * remove stores from schedule
         */
        $bind = [$this->getIdFieldName() => new \Zend_Db_Expr('NULL')];
        $where = ['storepickup_id IN(?)' => array_values(array_diff($old, $new))];
        $this->updateData($table, $bind, $where);

        /*
         * add stores to schedule
         */
        $bind = [$this->getIdFieldName() => new \Zend_Db_Expr($id)];
        $where = ['storepickup_id IN(?)' => array_values(array_diff($new, $old))];
        $this->updateData($table, $bind, $where);

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

        $collection->addFieldToSelect('storepickup_id')
            ->addFieldToFilter($this->getIdFieldName(), (int) $object->getId());

        return $collection;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return mixed
     */
    public function getStorepickupIds(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Magestore\Storepickup\Model\ResourceModel\Store\Collection $collection */
        $collection = $this->getStores($object);

        return $collection->getAllIds();
    }
}
