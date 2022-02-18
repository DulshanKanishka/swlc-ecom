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

namespace Magestore\Storepickup\Block\Adminhtml\Schedule\Edit;

use Magestore\Storepickup\Model\Factory;

/**
 * Schedule Edit Tabs.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * construct.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('schedule_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Schedule Information'));
    }

    /**
     * Preparing global layout.
     *
     * You can redefine this method in child classes for changing layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->addTab('general_section', 'schedule_edit_tab_general');

        // add stores tab
        $this->addTab(
            'stores_section',
            [
                'label' => __('Stores of Schedule'),
                'title' => __('Stores of Schedule'),
                'class' => 'ajax',
                'url' => $this->getUrl(
                    'storepickupadmin/ajaxtabgrid_store',
                    [
                        'entity_type' => Factory::MODEL_SCHEDULE,
                        'enitity_id' => $this->getRequest()->getParam('schedule_id'),
                        'serialized_name' => 'serialized_stores',
                    ]
                ),
            ]
        );

        return $this;
    }
}
