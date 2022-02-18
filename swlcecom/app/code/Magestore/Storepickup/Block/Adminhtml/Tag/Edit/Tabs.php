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

namespace Magestore\Storepickup\Block\Adminhtml\Tag\Edit;

use Magestore\Storepickup\Model\Factory;

/**
 * Tag Edit Tabs.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('tag_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Tag Information'));
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->addTab('general_section', 'tag_edit_tab_general');

        // add stores tab
        $this->addTab(
            'stores_section',
            [
                'label' => __('Stores of Tag'),
                'title' => __('Stores of Tag'),
                'class' => 'ajax',
                'url' => $this->getUrl(
                    'storepickupadmin/ajaxtabgrid_store',
                    [
                        'entity_type' => Factory::MODEL_TAG,
                        'enitity_id' => $this->getRequest()->getParam('tag_id'),
                        'serialized_name' => 'serialized_stores',
                    ]
                ),
            ]
        );

        return $this;
    }
}
