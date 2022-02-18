<?php

namespace Sunflowerbiz\CategoryPassword\Helper;
// Sunflowerbiz\CategoryPassword\Helper\ObjectManager::getObjectManager()
use \Magento\Framework\App\ObjectManager as MAGE_OM;

/**
 * Class ObjectManager
 *
 * @package Sunflowerbiz\CategoryPassword\Helper
 */
class ObjectManager
{
    /**
     * @return \Magento\Framework\App\ObjectManager
     */
    public static function getObjectManager(){
        return MAGE_OM::getInstance();
        }
}