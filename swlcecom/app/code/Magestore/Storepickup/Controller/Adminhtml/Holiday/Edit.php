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

use Magento\Framework\Controller\ResultFactory;

/**
 * Edit Holiday Action.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class Edit extends \Magestore\Storepickup\Controller\Adminhtml\Holiday
{
    /**
     * Edit Holiday.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam(static::PARAM_CRUD_ID);
        /** @var \Magestore\Storepickup\Model\Holiday $model */
        $model = $this->_createMainModel();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This Holiday no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_coreRegistry->register(static::REGISTRY_NAME, $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Holiday') : __('New Holiday'),
            $id ? __('Edit Holiday') : __('New Holiday')
        );

        $resultPage->getConfig()->getTitle()->prepend(__('Manage Holiday'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ?
            __('Edit Holiday %1', $this->_escaper->escapeHtml($model->getHolidayName())) : __('New Holiday')
        );

        return $resultPage;
    }
}
