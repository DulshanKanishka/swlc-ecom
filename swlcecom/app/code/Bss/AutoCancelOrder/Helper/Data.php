<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AutoCancelOrder
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AutoCancelOrder\Helper;

use Magento\Framework\App\Helper\Context;
use \Magento\Framework\App\ProductMetadataInterface;
use \Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Product metadata.
     *
     * @var ProductMetadataInterface
     */
    private $metadata;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var DateTime
     */
    protected $datetime;

    /**
     * Data constructor.
     * @param Context $context
     * @param ProductMetadataInterface $metadata
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param DateTime $datetime
     */
    public function __construct(
        Context $context,
        ProductMetadataInterface $metadata,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        DateTime $datetime
    ) {
        $this->metadata = $metadata;
        $this->timezone = $timezone;
        $this->datetime = $datetime;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            'bss_autocancelorder/general/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getCancelDate()
    {
        return $this->scopeConfig->getValue(
            'bss_autocancelorder/general/startdate',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getActiveOrderStatus()
    {
        return $this->scopeConfig->getValue(
            'bss_autocancelorder/general/order_status_option',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return array
     */
    public function getActivePaymentMethod()
    {
        $activePaymentMethod = $this->scopeConfig->getValue(
            'bss_autocancelorder/general/payment_method_group',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $magentoVersion = $this->metadata->getVersion();
        if ($magentoVersion >= '2.2.0') {
            return json_decode($activePaymentMethod, true);
        }

        return unserialize($activePaymentMethod);
    }

    /**
     * Gets the scope config timezone
     * @return string
     */
    public function getConfigTimezone()
    {
        return $this->scopeConfig->getValue(
            'general/locale/timezone',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES
        );
    }

    /**
     * Convert date from config timezone to Utc.
     * If pass \DateTime object as argument be sure that timezone is the same with config timezone
     *
     * @param string|\DateTimeInterface $date
     * @param string $format
     * @throws LocalizedException
     * @return string
     */
    public function convertConfigTimeToUtc($date, $format = 'Y-m-d H:i:s')
    {
        if (!($date instanceof $this->datetime)) {
            return $this->timezone->date($date)->format($format);
        } else {
            if ($date->getTimezone()->getName() !== $this->getConfigTimezone()) {
                throw new LocalizedException(
                    __('DateTime object timezone must be the same as config - %1', $this->getConfigTimezone())
                );
            }
        }
        return $this->timezone->date($date)->format($format);
    }
}
