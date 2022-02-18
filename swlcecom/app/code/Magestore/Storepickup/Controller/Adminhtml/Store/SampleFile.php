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
 * @package     Magestore_Shopbybrand
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Controller\Adminhtml\Store;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 *
 *
 * @category Magestore
 * @package  Magestore_Shopbybrand
 * @module   Pdfinvoiceplus
 * @author   Magestore Developer
 */
class SampleFile extends \Magestore\Storepickup\Controller\Adminhtml\Store
{
    /**
     * Execute action
     */
    public function execute()
    {
        $fileName = 'storepickup.csv';

        /** @var \Magento\Framework\App\Response\Http\FileFactory $fileFactory */
        $fileFactory = $this->_objectManager->get('Magento\Framework\App\Response\Http\FileFactory');

        return $fileFactory->create(
            $fileName,
            $this->getStorepickupSampleData(),
            DirectoryList::VAR_DIR
        );
    }

    public function getStorepickupSampleData()
    {
        /** @var \Magento\Framework\Module\Dir $moduleReader */
        $moduleReader = $this->_objectManager->get('Magento\Framework\Module\Dir');
        /** @var \Magento\Framework\Filesystem\DriverPool $drivePool */
        $drivePool = $this->_objectManager->get('Magento\Framework\Filesystem\DriverPool');
        $drive = $drivePool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);

        return $drive->fileGetContents($moduleReader->getDir('Magestore_Storepickup')
            . DIRECTORY_SEPARATOR . '_fixtures' . DIRECTORY_SEPARATOR . 'storepickup.csv');
    }
}
