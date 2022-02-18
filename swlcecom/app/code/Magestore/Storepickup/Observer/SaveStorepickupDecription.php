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
 * @package     Magestore_StorePickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Observer;

use Magento\Framework\Event\ObserverInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magestore\Storepickup\Model\Store;
use Magestore\Storepickup\Model\ResourceModel\Orders\StorepickupStatus;

/**
 * Class GiftMessageConfigObserver
 *
 * @category Magestore
 * @package  Magestore_StorePickup
 * @module   StorePickup
 * @author   Magestore Developer
 */
class SaveStorepickupDecription implements ObserverInterface
{
    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @codeCoverageIgnore
     */
    protected $_checkoutSession;

    /**
     * @var \Magestore\Storepickup\Model\StoreFactory
     */
    protected $_storeCollection;
    /**
     * @var \Magento\Sales\Api\Data\OrderAddressInterface
     */
    protected $_orderAddressInterface;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magestore\Storepickup\Model\StoreFactory $storeCollection,
        \Magento\Sales\Api\Data\OrderAddressInterface $orderAddressInterface
    ){
        $this->_checkoutSession = $checkoutSession;
        $this->_storeCollection = $storeCollection;
        $this->_orderAddressInterface = $orderAddressInterface;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            if($order->getShippingMethod()) { 
                if ($order->getShippingMethod(true)->getCarrierCode() == "storepickup") {
                    if ($this->_checkoutSession->getData('storepickup_session')) {
                        $new = $order->getShippingDescription();
                        $storepickup_session = $this->_checkoutSession->getData('storepickup_session', true);
                        $datashipping = array();
                        $storeId = $storepickup_session['store_id'];
                        $collectionstore = $this->_storeCollection->create();
                        $store = $collectionstore->load($storeId, 'storepickup_id');
                        //set shipping desciption

                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $gooleAPI = $objectManager->create('Magestore\Storepickup\Helper\Data')->getGoogleApiKey();

                        $pickupTime = '';
                        if (isset($storepickup_session['shipping_date']) && isset($storepickup_session['shipping_time'])) {
                            $pickupTime = $storepickup_session['shipping_date'].' '.$storepickup_session['shipping_time'];
                            $new .= '<br>' . __('Pickup date') . ' : ' . $storepickup_session['shipping_date'] . '<br>' . __('Pickup time') . ' : ' . $storepickup_session['shipping_time'] . '<br><img src="http://maps.google.com/maps/api/staticmap?key='.$gooleAPI.'&center=' . $store->getData('latitude') . ',' . $store->getData('longitude') . '&zoom=15&size=200x200&markers=color:red|label:S|' . $store->getData('latitude') . ',' . $store->getData('longitude') . '&sensor=false"/>';
                        } else {
                            $new .= '<br><img src="http://maps.google.com/maps/api/staticmap?key='.$gooleAPI.'&center=' . $store->getData('latitude') . ',' . $store->getData('longitude') . '&zoom=15&size=200x200&markers=color:red|label:S|' . $store->getData('latitude') . ',' . $store->getData('longitude') . '&sensor=false"/>';
                        }
                        $order->setShippingDescription($new);
                        //set shipping address
                        $datashipping['firstname'] = __('Store');
                        $datashipping['lastname'] = $store->getData('store_name');
                        $datashipping['street'][0] = $store->getData('address');
                        $datashipping['city'] = $store->getCity();
                        $datashipping['region'] = $store->getState();
                        $datashipping['postcode'] = $store->getData('zipcode');
                        $datashipping['country_id'] = $store->getData('country_id');
                        $datashipping['company'] = '';
                        if ($store->getFax()) {
                            $datashipping['fax'] = $store->getFax();
                        } else {
                            unset($datashipping['fax']);
                        }
                        if ($store->getPhone()) {
                            $datashipping['telephone'] = $store->getPhone();
                        } else {
                            unset($datashipping['telephone']);
                        }
                        $datashipping['save_in_address_book'] = 0;

                        $order->getShippingAddress()->addData($datashipping);
                        $order->setData('storepickup_id',$store->getId())
                            ->setData('storepickup_status',StorepickupStatus::STOREPICUP_PENDING)
                            ->setData('storepickup_time',$pickupTime)
                            ->setData('warehouse_id',$store->getWarehouseId());
                    }
                }
            }

        } catch (Exception $e) {

        }
    }
}