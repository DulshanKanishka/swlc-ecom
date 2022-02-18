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

namespace Magestore\Storepickup\Controller\Adminhtml;

use Magento\Framework\Exception\LocalizedException;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
abstract class AbstractAction extends \Magento\Backend\App\Action
{
    /**
     * param id for crud action : edit,delete,save.
     */
    const PARAM_CRUD_ID = 'entity_id';

    /**
     * registry name.
     */
    const REGISTRY_NAME = 'registry_model';

    /**
     * main model class name.
     *
     * @var string
     */
    protected $_mainModelName;

    /**
     * main collection class name.
     *
     * @var string
     */
    protected $_mainCollectionName;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Escaper.
     *
     * @var \Magento\Framework\Escaper
     */
    protected $_escaper;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $_massActionFilter;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface
     */
    protected $_mediaDirectory;

    /**
     * @var \Magestore\Storepickup\Helper\Image
     */
    protected $_imageHelper;

    /**
     * @var \Magento\Backend\Helper\Js
     */
    protected $_backendHelperJs;

    /**
     * AbstractAction constructor.
     *
     * @param \Magento\Backend\App\Action\Context     $context
     * @param \Magento\Framework\Escaper              $escaper
     * @param \Magento\Ui\Component\MassAction\Filter $massActionFilter
     * @param \Magento\Framework\Registry             $coreRegistry
     * @param \Magestore\Storepickup\Helper\Image    $imageHelper
     * @param \Magento\Backend\Helper\Js              $backendHelperJs
     * @param null                                    $mainModelName
     * @param null                                    $mainCollectionName
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Escaper $escaper,
        \Magento\Ui\Component\MassAction\Filter $massActionFilter,
        \Magento\Framework\Registry $coreRegistry,
        \Magestore\Storepickup\Helper\Image $imageHelper,
        \Magento\Backend\Helper\Js $backendHelperJs,
        $mainModelName = null,
        $mainCollectionName = null
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_massActionFilter = $massActionFilter;
        $this->_escaper = $escaper;
        $this->_imageHelper = $imageHelper;
        $this->_backendHelperJs = $backendHelperJs;
        $this->_mainModelName = $mainModelName;
        $this->_mainCollectionName = $mainCollectionName;
    }

    /**
     * create m.
     *
     * @return \Magento\Framework\Model\AbstractModel
     *
     * @throws LocalizedException
     */
    protected function _createMainModel()
    {
        /** @var \Magento\Framework\Model\AbstractModel $model */
        $model = $this->_objectManager->create($this->_mainModelName);
        if (!$model instanceof \Magento\Framework\Model\AbstractModel) {
            throw new LocalizedException(
                __('%1 isn\'t instance of Magento\Framework\Model\AbstractModel', get_class($model))
            );
        }

        return $model;
    }

    /**
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     *
     * @throws LocalizedException
     */
    protected function _createMainCollection()
    {
        /** @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection */
        $collection = $this->_objectManager->create($this->_mainCollectionName);
        if (!$collection instanceof \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection) {
            throw new LocalizedException(
                __(
                    '%1 isn\'t instance of Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection',
                    get_class($collection)
                )
            );
        }

        return $collection;
    }

    /**
     * get back result redirect after add/edit.
     *
     * @param \Magento\Backend\Model\View\Result\Redirect $resultRedirect
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function _getBackResultRedirect(\Magento\Backend\Model\View\Result\Redirect $resultRedirect, $paramCrudId = null)
    {
        switch ($this->getRequest()->getParam('back')) {
            case 'edit':
                $resultRedirect->setPath(
                    '*/*/edit',
                    [
                        static::PARAM_CRUD_ID => $paramCrudId,
                        '_current' => true,
                    ]
                );
                break;
            case 'new':
                $resultRedirect->setPath('*/*/new');
                break;
            default:
                $resultRedirect->setPath('*/*/');
        }

        return $resultRedirect;
    }

    /**
     * Check if admin has permissions to visit related pages.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magestore_Storepickup::storepickup');
    }
}
