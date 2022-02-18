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

namespace Magestore\Storepickup\Block\Adminhtml\Guide;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Prepare form before rendering HTML.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('guide_');

        /*
         * General Instructions
         */
        $fieldset = $form->addFieldset(
            'general_fieldset',
            [
                'legend' => __('General Instructions'),
                'class' => 'guide-fieldset',
            ]
        );

        $fieldset->addField(
            'general_instructions',
            'text',
            [
                'name' => 'general_instructions',
                'label' => __('General Instructions'),
                'title' => __('General Instructions'),
            ]
        )->setRenderer($this->getChildBlock('guide.general'));

        /*
         * guide for google API
         */
        $fieldset = $form->addFieldset(
            'google_fieldset',
            [
                'legend' => __('Instructions to create Google Map API Key'),
                'class' => 'guide-fieldset',
            ]
        );

        $fieldset->addField(
            'google',
            'text',
            [
                'name' => 'google',
                'label' => __('Instructions to create Google Map API Key'),
                'title' => __('Instructions to create Google Map API Key'),
            ]
        )->setRenderer($this->getChildBlock('guide.google'));

        /*
         * guide for facebook API
         */
        $fieldset = $form->addFieldset(
            'facebook_fieldset',
            [
                'legend' => __('Instructions to create Facebook API Key'),
                'class' => 'guide-fieldset',
            ]
        );

        $fieldset->addField(
            'facebook',
            'text',
            [
                'name' => 'facebook',
                'label' => __('Instructions to create Facebook API Key'),
                'title' => __('Instructions to create Facebook API Key'),
            ]
        )->setRenderer($this->getChildBlock('guide.facebook'));

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
