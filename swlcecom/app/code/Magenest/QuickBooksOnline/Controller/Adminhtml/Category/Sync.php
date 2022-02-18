<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Controller\Adminhtml\Category;

use Magenest\QuickBooksOnline\Controller\Adminhtml\Category as CategoryController;

/**
 * Class Sync
 * @package Magenest\QuickBooksOnline\Controller\Adminhtml\Category
 */
class Sync extends CategoryController
{
    /**
     * execute the action
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $update = $this->getRequest()->getParam('update');
        $collections = $this->_collectionFactory->create()
            ->addAttributeToSelect('name')
            ->setOrder('level', 'ASC')
            ->setOrder('position', 'ASC');
        /** @var \Magenest\QuickBooksOnline\Model\Synchronization\Category $items */
        $items = $this->_objectManager->create('Magenest\QuickBooksOnline\Model\Synchronization\Category');
        $totals = 0;
        try {
            /** @var \Magento\Catalog\Model\Category $collection */
            foreach ($collections as $collection) {
                $parentId = $collection->getParentId();
                if (!$parentId) {
                    continue;
                }
                $model = $this->_categoryFactory->create()->loadByCategoryId($parentId);
                $params = [
                    'Name' => $collection->getName(),
                    'Type' => 'Category',
                ];
                $level = $collection->getLevel();

                if ($level > 1 && $level < 5) {
                    $params['SubItem'] = true;
                    $params['ParentRef']['value'] = $model->getQboId();
                }

                $model = $this->_categoryFactory->create()->loadByCategoryId($collection->getId());
                if ($model->getId() && !$update) {
                    continue;
                } else {
                    if ($model->getId()) {
                        $params['Id'] = $model->getQboId();
                        $params['SyncToken'] = $model->getSyncToken();
                    }
                }
                try {
                    $response = $items->sync($params);
                    if (is_array($response)) {
                        $id = $response['Item']['Id'];
                        $model->setCategoryName($collection->getName());
                        $model->setCatId($collection->getId());
                        $model->setSyncToken($response['Item']['SyncToken']);
                        $model->setQboId($id);
                        $model->save();
                        $totals++;
                    }
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(__('Can\'t sync Category Name: %1', $collection->getName()));
                    continue;
                }
                if (!$update) {
                    $this->messageManager->addSuccessMessage(__('A total of %1 categories have been synchronized to QuickBooksOnline.', $totals));
                } else {
                    $this->messageManager->addSuccessMessage(__('A total of %1 categories have been updated to QuickBooksOnline.', $totals));
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $this->_redirect('*/*/index');
    }
}
