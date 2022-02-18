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

namespace Magestore\Storepickup\Block;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Wrapper extends \Magestore\Storepickup\Block\AbstractBlock
{
    protected $_template = 'Magestore_Storepickup::wrapper.phtml';
    /**
     * @var \Magestore\Storepickup\Model\ResourceModel\Store\CollectionFactory
     *
     */

    //protected $_storeCollectionFactory;

    /**
     * Block constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array                                            $data
     */
    public function __construct(
        \Magestore\Storepickup\Block\Context $context,
        //\Magestore\Storepickup\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        //$this->_storeCollectionFactory = $storeCollectionFactory;
    }

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getListStore()
    {
        /** @var \Magestore\Storepickup\Model\ResourceModel\Store\Collection $collection */
        $collection = $this->_storeCollectionFactory->create();
        $collection->addFieldToFilter('status','1')->addFieldToSelect(['storepickup_id', 'store_name','address','phone','latitude','longitude']);
        $collection->setOrder('store_name', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC);
        return \Zend_Json::encode($collection->getData());
    }
}
