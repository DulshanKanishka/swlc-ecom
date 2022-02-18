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

namespace Magestore\Storepickup\Block\Store;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class ViewPage extends \Magestore\Storepickup\Block\AbstractBlock
{
    /**
     * Block constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array                                            $data
     */
    public function __construct(
        \Magestore\Storepickup\Block\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return \Magestore\Storepickup\Model\Store
     */
    public function getStore()
    {
        return $this->_coreRegistry->registry('storepickup_store');
    }

    /**
     * Get schedule time html in a day.
     *
     * $day can be 'monday', 'tuesday', ...
     *
     * @param \Magestore\Storepickup\Model\Store $store
     * @param string                              $day
     */
    public function getScheduleTimeHtml(\Magestore\Storepickup\Model\Store $store, $day)
    {
        if ($store->isOpenday($day)) {
            if ($store->hasBreakTime($day)) {
                return sprintf(
                    '<td>%s - %s && %s - %s</td>',
                    $store->getData($day . '_open'),
                    $store->getData($day . '_open_break'),
                    $store->getData($day . '_close_break'),
                    $store->getData($day . '_close')
                );
            } else {
                return sprintf(
                    '<td>%s - %s</td>',
                    $store->getData($day . '_open'),
                    $store->getData($day . '_close')
                );
            }
        } else {
            return '<td>' . __('Closed') . '</td>';
        }
    }

    /**
     * @return array
     */
    public function getHashWeekdays()
    {
        return [
            'monday' => __('Mon'),
            'tuesday' => __('Tue'),
            'wednesday' => __('Wed'),
            'thursday' => __('Thur'),
            'friday' => __('Fri'),
            'saturday' => __('Sat'),
            'sunday' => __('Sun'),
        ];
    }

    /**
     * Get current Url.
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->_urlBuilder->getCurrentUrl();
    }

    public function getStoreJson(\Magestore\Storepickup\Model\Store $store)
    {
        return $store->toJson([
            'storepickup_id',
            'latitude',
            'longitude',
            'zoom_level',
            'marker_icon',
            'baseimage',
            'store_name',
            'address',
            'phone',
            'email',
            'fax',
            'link',
        ]);
    }
}
