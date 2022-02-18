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

namespace Magestore\Storepickup\Block\Adminhtml\Tag;

/**
 * Tag Edit Form Container.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry           $registry
     * @param array                                 $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     */
    protected function _construct()
    {
        $this->_objectId = 'tag_id';
        $this->_blockGroup = 'Magestore_Storepickup';
        $this->_controller = 'adminhtml_tag';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Tag'));
        $this->buttonList->update('delete', 'label', __('Delete'));

        $this->buttonList->add(
            'saveandcontinue',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                ],
            ],
            -100
        );

        $this->buttonList->add(
            'new-button',
            [
                'label' => __('Save and New'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => ['event' => 'saveAndNew', 'target' => '#edit_form'],
                    ],
                ],
            ],
            10
        );

        $this->_formScripts[] = '
        function toggleEditor() {
                if (tinyMCE.getInstanceById(\'tag_content\') == null) {
                    tinyMCE.execCommand(\'mceAddControl\', false, \'tag_content\');
                } else {
                    tinyMCE.execCommand(\'mceRemoveControl\', false, \'tag_content\');
                }
            }
        
                              
                    require([
                            "jquery",
                            "underscore",
                            "mage/mage",
                            "mage/backend/tabs",
                            "domReady!"
                        ], function($) {
                       
                            var $form = $(\'#edit_form\');
                            $form.mage(\'form\', {
                                handlersData: {
                                    save: {},
                                    saveAndNew: {
                                        action: {
                                            args: {back: \'new\'}
                                        }
                                    },
                                }
                            });

                        });   
        
        ';
    }
}
