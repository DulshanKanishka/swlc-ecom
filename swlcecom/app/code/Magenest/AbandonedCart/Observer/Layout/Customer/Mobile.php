<?php

namespace Magenest\AbandonedCart\Observer\Layout\Customer;

use Magento\Framework\Event\Observer;

class Mobile implements \Magento\Framework\Event\ObserverInterface
{
    /** @var \Magenest\AbandonedCart\Helper\Data $_helperData */
    protected $_helperData;

    /**
     * Mobile constructor.
     *
     * @param \Magenest\AbandonedCart\Helper\Data $dataHelper
     */
    public function __construct(
        \Magenest\AbandonedCart\Helper\Data $dataHelper
    ) {
        $this->_helperData = $dataHelper;
    }

    public function execute(Observer $observer)
    {
        $request  = $this->_helperData->getRequest();
        $pathInfo = $request->getPathInfo();
        if ($pathInfo == '/customer/account/create/') {
            $is_allow = $this->_helperData->getConfig('abandonedcart/nexmo/nexmo_enable');
            if ($is_allow) {
                $observer->getEvent()->getLayout()->getUpdate()->addHandle('customer_account_create_mobile_input');
            }
        }
    }
}