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

namespace Magestore\Storepickup\Block\Adminhtml\Widget\Order;

use Magestore\Storepickup\Block\Adminhtml\Widget\Grid\Column\Filter\Checkbox as FilterCheckbox;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Grid extends \Magento\Backend\Block\Widget\Grid
{
    /**
     * @var \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter
     */
    protected $_converter;

    /**
     * @var \Magestore\Storepickup\Helper\Data
     */
    protected $_storepickupHelper;

    /**
     * Request
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data            $backendHelper
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magestore\Storepickup\Helper\Data $storepickupHelper,
        \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter $converter,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->_request = $context->getRequest();
        $this->_storepickupHelper = $storepickupHelper;
        $this->_converter = $converter;

        if ($this->hasData('serialize_grid') && count($this->getSelectedRows())) {
            $this->setDefaultFilter(
                ['checkbox_id' => FilterCheckbox::CHECKBOX_YES]
            );
        }
    }

    public function _prepareCollection()
    {
        if($storepickupId = $this->_request->getParam('storepickup_id'))
            return parent::_prepareCollection()->getCollection()->addFieldToFilter('storepickup_id',$storepickupId);
        return parent::_prepareCollection();
    }

    /**
     * get selected row values.
     *
     * @return array
     */
    public function getSelectedRows()
    {
        $selectedValues = $this->_converter->toFlatArray(
            $this->_storepickupHelper->getTreeSelectedValues()
        );

        return array_values($selectedValues);
    }
}
