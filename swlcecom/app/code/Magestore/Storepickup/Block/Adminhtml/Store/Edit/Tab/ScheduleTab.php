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

namespace Magestore\Storepickup\Block\Adminhtml\Store\Edit\Tab;

/**
 * General Tab.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class ScheduleTab extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magestore\Storepickup\Model\Store\Option\Schedule
     */
    protected $_scheduleOption;

    /**
     * ScheduleTab constructor.
     *
     * @param \Magento\Backend\Block\Template\Context             $context
     * @param \Magento\Framework\Registry                         $registry
     * @param \Magento\Framework\Data\FormFactory                 $formFactory
     * @param \Magestore\Storepickup\Model\Store\Option\Schedule $scheduleOption
     * @param array                                               $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magestore\Storepickup\Model\Store\Option\Schedule $scheduleOption,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_scheduleOption = $scheduleOption;
    }

    /**
     * Prepare form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magestore\Storepickup\Model\Store $model */
        $model = $this->getRegistryModel();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('store_');

        /** @var \Magento\Framework\Data\Form\Element\Fieldset $fieldset */
        $fieldset = $form->addFieldset(
            'scheudle_fieldset',
            [
                'legend' => __('Time Schedule'),
            ]
        );

        $fieldset->addField(
            'schedule_id',
            'select',
            [
                'name' => 'schedule_id',
                'label' => __('Schedule'),
                'title' => __('Schedule'),
                'values' => array_merge(
                    [
                        ['value' => '', 'label' => __('-------- Please select a Schedule --------')],
                    ],
                    $this->_scheduleOption->toOptionArray()
                ),
                'note' => $this->_getNoteCreateSchedule(),
            ]
        );

        /** @var \Magestore\Storepickup\Block\Adminhtml\Store\Edit\Tab\ScheduleTab\Renderer\ScheduleTable $scheduleTableBlock */
        $scheduleTableBlock = $this->getLayout()
            ->createBlock('Magestore\Storepickup\Block\Adminhtml\Store\Edit\Tab\ScheduleTab\Renderer\ScheduleTable');

        $fieldset->addField(
            'schedule_table',
            'text',
            [
                'name' => 'schedule_table',
                'label' => __('Schedule Table'),
            ]
        )->setRenderer($scheduleTableBlock);

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * get note create new schedule.
     *
     * @return mixed
     */
    protected function _getNoteCreateSchedule()
    {
        return sprintf(
            '<a href="%s" target="_blank">%s</a> %s',
            $this->getUrl('storepickupadmin/schedule/new'),
            __('Click here'),
            __('to go to page create new schedule.')
        );
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
     * Return Tab label.
     *
     * @return string
     *
     * @api
     */
    public function getTabLabel()
    {
        return __('Store\'s Schedule');
    }

    /**
     * Return Tab title.
     *
     * @return string
     *
     * @api
     */
    public function getTabTitle()
    {
        return __('Store\'s Schedule');
    }

    /**
     * Can show tab in tabs.
     *
     * @return bool
     *
     * @api
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden.
     *
     * @return bool
     *
     * @api
     */
    public function isHidden()
    {
        return false;
    }
}
