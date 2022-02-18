<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\Xero\Controller\Adminhtml\App;

use Magento\Framework\App\ResponseInterface;
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\App\Action\Context;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magenest\Xero\Model\Helper;

class Download extends \Magento\Config\Controller\Adminhtml\System\AbstractConfig
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    protected $_scopeConfig;

    protected $_fileConfigPath = [
        '.cer' => [
            'path' => 'magenest_xero_config/xero_api/public_key',
            'file_name' => 'magenest_xero_config/xero_api/public_key_file'
        ],
        '.pem' => [
            'path' => 'magenest_xero_config/xero_api/private_key',
            'file_name' => 'magenest_xero_config/xero_api/private_key_file'
        ]
    ];

    protected $_xeroHelper;

    /**
     * Download constructor.
     * @param Context $context
     * @param Structure $configStructure
     * @param ConfigSectionChecker $sectionChecker
     * @param FileFactory $fileFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        Structure $configStructure,
        ConfigSectionChecker $sectionChecker,
        FileFactory $fileFactory,
        ScopeConfigInterface $scopeConfig,
        Helper $helper
    ) {
        $this->_fileFactory = $fileFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_xeroHelper = $helper;
        parent::__construct($context, $configStructure, $sectionChecker);
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $fileEx = $this->getRequest()->getParam('file_extension');
        $website = $this->getRequest()->getParam('website');
        if ($website) {
            $this->_xeroHelper->setScope('websites');
            $this->_xeroHelper->setScopeId($website);
        }
        $fileContent = $this->_xeroHelper->getConfig($this->_fileConfigPath[$fileEx]['path']);
        $fileName = $this->_xeroHelper->getConfig($this->_fileConfigPath[$fileEx]['file_name']);
        return $this->_fileFactory->create(
            $fileName,
            $fileContent,
            DirectoryList::TMP
        );
    }
}
