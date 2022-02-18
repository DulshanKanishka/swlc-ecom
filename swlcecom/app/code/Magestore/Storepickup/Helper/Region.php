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

namespace Magestore\Storepickup\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Helper Data.
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Region extends AbstractHelper
{

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\Collection
     */
    protected $_regionCollectionFactory;

    /**
     * Region constructor.
     * @param Context $context
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     */
    public function __construct(
       Context $context,
       \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
    ) {
        $this->_regionCollectionFactory = $regionCollectionFactory;
        parent::__construct($context);
    }

    /**
     * error state code
     */
    const STATE_ERROR = -1;
    /**
     * @param $country_id
     * @param $state_name
     * @return int
     */
    public function validateState($country_id, $state_name){
        $collection = $this->_regionCollectionFactory->create();
        $collection->addCountryFilter($country_id);

        if($state_name == ''){
            return self::STATE_ERROR;
        }

        if(sizeof($collection) > 0){
            $region_id = self::STATE_ERROR;
            foreach ($collection as $region){
                if(strcasecmp($state_name,$region->getData('name')) == 0){
                    $region_id = $region->getId();
                    break;
                }
            }
            return $region_id;
        } else {
            return 0;
        }
    }

}
