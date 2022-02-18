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

namespace Magestore\Storepickup\Controller\Adminhtml\Order;

use Magestore\Storepickup\Model\ResourceModel\Store\Grid\StatusesArray;
use Magestore\Storepickup\Model\ResourceModel\Orders\StorepickupStatus;

/**
 * MassDisable Store Action.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class MassPrepare extends \Magestore\Storepickup\Controller\Adminhtml\Order
{
    /**
     * Execute action.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     *
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
//        $collection = $this->_massActionFilter->getCollection($this->_createMainCollection());
        $collection = $this->_createMainCollection()->addFieldToFilter('entity_id',['in'=>$this->getRequest()->getParam('order_id')]);
        foreach ($collection as $item) {
            if($item->getStorepickupStatus()<StorepickupStatus::STOREPICUP_PREPARE){
                $item->setStorepickupStatus(StorepickupStatus::STOREPICUP_PREPARE);
                $item->save();
            }
            $storeId = $item->getStorepickupId();
        }

        $this->messageManager->addSuccess(__('A total of %1 record(s) have been prepare.', $collection->getSize()));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if(isset($storeId)){
            return $resultRedirect->setPath('storepickupadmin/store/edit',['storepickup_id'=>$storeId]);
        }
        return $resultRedirect->setPath('storepickupadmin/store/index');
    }
}
