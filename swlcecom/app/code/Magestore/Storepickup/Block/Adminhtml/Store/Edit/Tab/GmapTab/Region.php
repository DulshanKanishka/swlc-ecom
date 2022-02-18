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

namespace Magestore\Storepickup\Block\Adminhtml\Store\Edit\Tab\GmapTab;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Region extends \Magento\Backend\Block\Template
{
    protected $_template = 'Magestore_Storepickup::store/region.phtml';

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $_directoryHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Directory\Helper\Data          $directoryHelper
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_directoryHelper = $directoryHelper;
    }

    /**
     * @return string
     */
    public function getRegionJson()
    {
        return $this->_directoryHelper->getRegionJson();
    }

    /**
     * get registry model.
     *
     * @return \Magestore\Storepickup\Model\Store
     */
    public function getStore()
    {
        return $this->getParentBlock()->getRegistryModel();
    }
}
