<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_MassOrderActions
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\MassOrderActions\Block\Adminhtml\System;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory;
use Mageplaza\MassOrderActions\Helper\Data;
use Mageplaza\MassOrderActions\Model\Config\Source\System\Actions as MassActions;

/**
 * Class IconManage
 * @package Mageplaza\ProductAttachments\Block\Adminhtml\System
 */
class Actions extends AbstractFieldArray
{
    /**
     * @var string
     */
    protected $_template = 'Mageplaza_MassOrderActions::system/config/sort-actions.phtml';

    /**
     * @var Factory
     */
    protected $_elementFactory;

    /**
     * @var Data
     */
    protected $_helperData;

    /**
     * @var MassActions
     */
    protected $_massAction;

    /**
     * IconManage constructor.
     *
     * @param Context $context
     * @param Factory $elementFactory
     * @param Data $helperData
     * @param MassActions $massActions
     * @param array $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        Data $helperData,
        MassActions $massActions,
        array $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        $this->_helperData = $helperData;
        $this->_massAction = $massActions;

        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('selected', ['label' => __('Select')]);
        $this->addColumn('action', ['label' => __('Action')]);
        $this->addColumn('position', ['label' => __('Position'), 'class' => 'required-entry']);
    }

    /**
     * @return array
     */
    public function getMassActions()
    {
        return $this->_massAction->toOptionArray();
    }
}
