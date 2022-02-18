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

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Abstract class Export.
 *
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
abstract class AbstractExportAction extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @param Context $context
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->_fileFactory = $fileFactory;
    }

    /**
     * Export store grid to Excel XML format.
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        return $this->_fileFactory->create(
            $this->_getFileName(),
            $this->_getContent(),
            DirectoryList::VAR_DIR
        );
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

    /**
     * file name to export.
     *
     * @return string
     */
    abstract protected function _getFileName();

    /**
     * content to export file.
     *
     * @return string
     */
    abstract protected function _getContent();
}
