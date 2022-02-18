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

namespace Magestore\Storepickup\Controller\Adminhtml\Store;

/**
 * Save Store Action.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Save extends \Magestore\Storepickup\Controller\Adminhtml\Store
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data = $this->getRequest()->getPostValue()) {
            $id = $this->getRequest()->getParam(static::PARAM_CRUD_ID);

            /** @var \Magestore\Storepickup\Model\Store $model */
            $model = $this->_createMainModel()->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addError(__('This Store no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }

            $model->setData($data);

            $this->_prepareSerializedData($model);

            try {
                $this->_imageHelper->mediaUploadImage(
                    $model,
                    'marker_icon',
                    \Magestore\Storepickup\Model\Store::MARKER_ICON_RELATIVE_PATH,
                    $makeResize = true
                );

                $model->save();

                $this->messageManager->addSuccess(__('The Store has been saved.'));
                $this->_getSession()->setFormData(false);

                return $this->_getBackResultRedirect($resultRedirect, $model->getId());
            } catch (\Exception $e) {
                $this->messageManager->addError(__('Something went wrong while saving the Store.'));
                $this->messageManager->addError($e->getMessage());
                $this->_getSession()->setFormData($data);

                $this->_getSession()->setSerializedTags($model->getData('serialized_tags'));
                $this->_getSession()->setSerializedHolidays($model->getData('serialized_holidays'));
                $this->_getSession()->setSerializedSpecialdays($model->getData('serialized_specialdays'));

                return $resultRedirect->setPath('*/*/edit', [static::PARAM_CRUD_ID => $id]);
            }
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Prepare serialized data for model.
     *
     * @param \Magestore\Storepickup\Model\Store $model
     *
     * @return $this
     */
    protected function _prepareSerializedData(\Magestore\Storepickup\Model\Store $model)
    {
        if ($model->hasData('serialized_tags')) {
            $model->setData(
                'in_tag_ids',
                $this->_backendHelperJs->decodeGridSerializedInput($model->getData('serialized_tags'))
            );
        }

        if ($model->hasData('serialized_holidays')) {
            $model->setData(
                'in_holiday_ids',
                $this->_backendHelperJs->decodeGridSerializedInput($model->getData('serialized_holidays'))
            );
        }

        if ($model->hasData('serialized_specialdays')) {
            $model->setData(
                'in_specialday_ids',
                $this->_backendHelperJs->decodeGridSerializedInput($model->getData('serialized_specialdays'))
            );
        }

        return $this;
    }
}
