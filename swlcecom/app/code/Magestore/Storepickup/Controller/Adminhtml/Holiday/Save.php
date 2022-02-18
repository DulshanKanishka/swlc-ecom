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

namespace Magestore\Storepickup\Controller\Adminhtml\Holiday;

/**
 * Save Holiday Action.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Save extends \Magestore\Storepickup\Controller\Adminhtml\Holiday
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

            /** @var \Magestore\Storepickup\Model\Holiday $model */
            $model = $this->_createMainModel()->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addError(__('This Holiday no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }

            $model->setData($data);

            if ($model->hasData('serialized_stores')) {
                $model->setData(
                    'in_storepickup_ids',
                    $this->_backendHelperJs->decodeGridSerializedInput($model->getData('serialized_stores'))
                );
            }

            try {
                $model->save();

                $this->messageManager->addSuccess(__('The Holiday has been saved.'));
                $this->_getSession()->setFormData(false);

                return $this->_getBackResultRedirect($resultRedirect, $model->getId());
            } catch (\Exception $e) {
                $this->messageManager->addError(__('Something went wrong while saving the Holiday.'));
                $this->_getSession()->setFormData($data);

                $this->_getSession()->setSerializedStores($model->getData('serialized_stores'));

                return $resultRedirect->setPath('*/*/edit', [static::PARAM_CRUD_ID => $id]);
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
