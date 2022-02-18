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

namespace Magestore\Storepickup\Controller\Checkout;

class ChangeTime extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * CheckShipping constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    protected $_checkoutSession;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_checkoutSession = $checkoutSession;
    }
    public function execute()
    {
        $storepickup_session = $this->_checkoutSession->getData('storepickup_session');
        $storepickup_session['store_id'] = $this->getRequest()->getParam('store_id');
        $storepickup_session['shipping_date'] = $this->getRequest()->getParam('shipping_date');
        $storepickup_session['shipping_time'] = $this->getRequest()->getParam('shipping_time');
        $this->_checkoutSession->setData('storepickup_session',$storepickup_session);
        return $this->getResponse()->setBody(\Zend_Json::encode($storepickup_session));
    }
}
