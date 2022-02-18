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
 * @package     Magestore_Giftvoucher
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Controller\Adminhtml\Checkout;

class DisableDate extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;
    /**
     * @var \Magestore\Storepickup\Model\StoreFactory
     */
    protected $_storeCollection;

    /**
     * DisableDate constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magestore\Storepickup\Model\StoreFactory $storeCollection
     */

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magestore\Storepickup\Model\StoreFactory $storeCollection
    )
    {

        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_storeCollection = $storeCollection;
        parent::__construct($context);
    }
    public function execute()
    {
        $date = array();
        $closed = array();
        $holiday_date = array();
        $storeId = $this->getRequest()->getParam('store_id');
        $collectionstore = $this->_storeCollection->create();
        $store = $collectionstore->load($storeId,'storepickup_id');
        $holidaysdata = $store->getHolidaysData();
        foreach($holidaysdata as $holidays){
            foreach($holidays['date'] as $_date){
                $holiday_date[]=date("m/d/Y", strtotime($_date));
            }
        }
        if(!$store->isOpenday('monday')) $closed[]=1;
        if(!$store->isOpenday('tuesday')) $closed[]=2;
        if(!$store->isOpenday('wednesday')) $closed[]=3;
        if(!$store->isOpenday('thursday')) $closed[]=4;
        if(!$store->isOpenday('friday')) $closed[]=5;
        if(!$store->isOpenday('saturday')) $closed[]=6;
        if(!$store->isOpenday('sunday')) $closed[]=0;
        $date['holiday'] = $holiday_date;
        $date['schedule'] = $closed;

        return $this->getResponse()->setBody(\Zend_Json::encode($date));
    }
}
