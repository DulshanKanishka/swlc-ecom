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

namespace Magestore\Storepickup\Controller\Adminhtml\Specialday;

use Magento\Framework\Controller\ResultFactory;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class ExportCsv extends \Magestore\Storepickup\Controller\Adminhtml\AbstractExportAction
{
    /**
     * file name to export.
     *
     * @return string
     */
    protected function _getFileName()
    {
        return 'specialdays.csv';
    }

    /**
     * get content.
     *
     * @return string
     */
    protected function _getContent()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        /** @var \Magento\Backend\Block\Widget\Grid\ExportInterface $exportBlock */
        $exportBlock = $resultPage->getLayout()
            ->getChildBlock('storepickupadmin.specialday.grid', 'grid.export');

        return $exportBlock->getCsvFile();
    }

    /**
     * Check the permission to run it.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magestore_Storepickup::specialday');
    }
}
