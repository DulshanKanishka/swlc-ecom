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

namespace Magestore\Storepickup\Block\Adminhtml\Store\Edit\Tab\ScheduleTab\Renderer;

use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class ScheduleTable extends \Magento\Backend\Block\Widget implements RendererInterface
{
    protected $_template = 'Magestore_Storepickup::store/scheduletable.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Model Url instance.
     *
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_backendUrl;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\UrlFactory $backendUrlFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->_backendUrl = $backendUrlFactory->create();
    }

    /**
     * Preparing global layout.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $tableGrid = $this->getLayout()
            ->createBlock('Magestore\Storepickup\Block\Adminhtml\Store\Edit\Tab\ScheduleTab\TableGrid');

        /** @var \Magestore\Storepickup\Model\Store $store */
        $store = $this->getRegistryModel();
        $tableGrid->setData('schedule_id', $store->getScheduleId());
        $this->setChild('schedule_table_grid', $tableGrid);

        return parent::_prepareLayout();
    }

    /**
     * get registry model.
     *
     * @return \Magestore\Storepickup\Model\Store
     */
    public function getRegistryModel()
    {
        return $this->_coreRegistry->registry('storepickup_store');
    }

    /**
     * Render form element as HTML.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);

        return $this->toHtml();
    }

    /**
     * @return \Magestore\Storepickup\Model\Store
     */
    public function getRegistyStore()
    {
        return $this->_coreRegistry->registry('storepickup_store');
    }

    /**
     * get url to load schedule table grid by ajax.
     *
     * @return string
     */
    public function getAjaxLoadScheduleUrl()
    {
        return $this->_backendUrl->getUrl('storepickupadmin/store/scheduletable');
    }
}
