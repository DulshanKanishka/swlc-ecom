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

namespace Magestore\Storepickup\Model;

/**
 * Model Schedule.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Schedule extends \Magestore\Storepickup\Model\AbstractModelManageStores
{
    /**
     * Model construct that should be used for object initialization.
     */
    public function _construct()
    {
        $this->_init('Magestore\Storepickup\Model\ResourceModel\Schedule');
    }

    /**
     * Processing object before save data.
     */
    public function beforeSave()
    {
        $this->_prepareSaveWeekdayTime();

        return parent::beforeSave();
    }

    /**
     * convert weekday time to string.
     *
     * @param $weekday
     * @param $suffix
     */
    protected function _convertWeekdayTime($weekday, $suffix)
    {
        if (is_array($this->getData($weekday . $suffix))) {
            $this->setData($weekday . $suffix, implode(':', $this->getData($weekday . $suffix)));
        }

        return $this->getData($weekday . $suffix);
    }

    /**
     * prepare save Weekday data.
     *
     * @param $weekday
     * @param $suffix
     */
    protected function _prepareSaveWeekdayTime()
    {
        $suffixes = ['_open', '_open_break', '_close_break', '_close'];
        foreach ($this->getWeekdays() as $weekday) {
            foreach ($suffixes as $suffix) {
                $this->_convertWeekdayTime($weekday, $suffix);
            }
        }
    }

    /**
     * get weekday code.
     *
     * @return array
     */
    public function getWeekdays()
    {
        return [
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        ];
    }
}
