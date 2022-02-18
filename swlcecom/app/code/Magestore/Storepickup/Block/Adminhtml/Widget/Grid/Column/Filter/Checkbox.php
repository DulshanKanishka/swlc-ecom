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

namespace Magestore\Storepickup\Block\Adminhtml\Widget\Grid\Column\Filter;

/**
 * Class Checkbox.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Checkbox extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Checkbox
{
    /**
     * checkboxe fitler value.
     */
    const CHECKBOX_YES = 1;
    const CHECKBOX_NO = 0;

    /**
     * @var \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter
     */
    protected $_converter;

    /**
     * Checkbox constructor.
     *
     * @param \Magento\Backend\Block\Context                                       $context
     * @param \Magento\Framework\DB\Helper                                         $resourceHelper
     * @param \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter $converter
     * @param array                                                                $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter $converter,
        array $data = []
    ) {
        parent::__construct($context, $resourceHelper, $data);
        $this->_converter = $converter;
    }

    /**
     * get search condition of checkbox column in_storepickup.
     *
     * @return array
     */
    public function getCondition()
    {
        $values = $this->_converter->toFlatArray($this->getColumn()->getValues());

        if ($this->getValue()) {
            return [['in' => $values]];
        } else {
            return [['nin' => $values]];
        }
    }
}
