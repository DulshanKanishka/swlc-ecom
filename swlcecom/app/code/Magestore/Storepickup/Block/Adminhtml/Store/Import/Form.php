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

namespace Magestore\Storepickup\Block\Adminhtml\Store\Import;

use Magento\Backend\Block\Widget\Form\Generic;

/**
 * Class Tab GeneralTab
 */
class Form extends Generic
{
    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'      => 'edit_form',
                    'action'  => $this->getUrl('*/*/importProcess'),
                    'method'  => 'post',
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );

        $fieldset = $form->addFieldset('general_fieldset', ['legend' => __('Import Information')]);

        $fieldset->addField(
            'filecsv',
            'file',
            [
                'title'    => __('Import File'),
                'label'    => __('Import File'),
                'name'     => 'filecsv',
                'required' => true,
                'note'     => 'Only csv file is supported. Click <a target="_blank" href="'
                    . $this->getUrl('storepickupadmin/store/sampleFile')
                    . '">here</a> to download the Sample CSV file',
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
