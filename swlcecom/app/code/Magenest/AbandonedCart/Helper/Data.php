<?php

namespace Magenest\AbandonedCart\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ABANDONED_CART_PERIOD = "abandonedcart/setting/considered_member";

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
    protected $scopeConfig;

    /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
    protected $storeManager;

    /** @var \Magento\Customer\Model\Session $_customerSession */
    protected $_customerSession;

    /**
     * Data constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param Context $context
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->storeManager     = $storeManager;
        $this->_customerSession = $customerSession;
        $this->scopeConfig      = $context->getScopeConfig();
        parent::__construct($context);
    }

    public function getCustomerId()
    {
        return $this->_customerSession->getId();
    }

    public function getAbandonedCartPeriod()
    {
        $timePeriod = $this->scopeConfig->getValue(self::ABANDONED_CART_PERIOD);
        if ($timePeriod == '' || $timePeriod == null) {
            $timePeriod = '60';
        }
        return $timePeriod;
    }

    public function formateDate($minutes)
    {
        $modify = '+' . $minutes . ' minutes';
        $now    = new \DateTime();
        $now->modify($modify);
        $date = $now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        return $date;
    }

    public function getConfig($path)
    {
        $value = $this->scopeConfig->getValue($path);
        return $value;
    }

    public function getRequest()
    {
        return $this->_request;
    }

    public function getVersionMagento()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        try {
            $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
            $version         = $productMetadata->getVersion();
        } catch (\Exception $e) {
            $version = '0.0.0';
            $this->_logger->critical($e->getMessage());
        }
        return $version;
    }
}