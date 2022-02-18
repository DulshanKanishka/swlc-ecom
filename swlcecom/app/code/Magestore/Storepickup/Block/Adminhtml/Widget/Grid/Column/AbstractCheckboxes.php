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

namespace Magestore\Storepickup\Block\Adminhtml\Widget\Grid\Column;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
abstract class AbstractCheckboxes extends \Magento\Backend\Block\Widget\Grid\Column
{
    /**
     * @var \Magestore\Storepickup\Helper\Data
     */
    protected $_storepickupHelper;

    /**
     * @var \Magestore\Storepickup\Model\StoreFactory
     */
    protected $_storeFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magestore\Storepickup\Helper\Data $storepickupHelper,
        \Magestore\Storepickup\Model\StoreFactory $storeFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_storepickupHelper = $storepickupHelper;
        $this->_storeFactory = $storeFactory;

        $this->_filterTypes['checkbox'] = 'Magestore\Storepickup\Block\Adminhtml\Widget\Grid\Column\Filter\Checkbox';
    }

    /**
     * values.
     *
     * @return mixed
     */
    public function getValues()
    {
        if (!$this->hasData('values')) {
            $this->setData('values', $this->getSelectedValues());
        }

        return $this->getData('values');
    }

    /**
     * get selected rows.
     *
     * @return array
     */
    abstract public function getSelectedValues();
}
