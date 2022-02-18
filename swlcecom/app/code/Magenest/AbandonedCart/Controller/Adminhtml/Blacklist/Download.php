<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Blacklist;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\App\Filesystem\DirectoryList;

class Download extends \Magenest\AbandonedCart\Controller\Adminhtml\Blacklist
{
    const SAMPLE_FILES_MODULE = 'Magenest_AbandonedCart';

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    protected $readFactory;

    /**
     * @var \Magento\Framework\Component\ComponentRegistrar
     */
    protected $componentRegistrar;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * Download constructor.
     *
     * @param \Magenest\AbandonedCart\Model\BlackListFactory $blacklistFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory
     * @param ComponentRegistrar $componentRegistrar
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\BlackListFactory $blacklistFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory,
        \Magento\Framework\Component\ComponentRegistrar $componentRegistrar,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->fileFactory        = $fileFactory;
        $this->resultRawFactory   = $resultRawFactory;
        $this->readFactory        = $readFactory;
        $this->componentRegistrar = $componentRegistrar;
        parent::__construct($blacklistFactory, $logger, $registry, $pageFactory, $context);
    }

    public function execute()
    {
        $fileName         = $this->getRequest()->getParam('filename') . '.csv';
        $moduleDir        = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::SAMPLE_FILES_MODULE);
        $fileAbsolutePath = $moduleDir . '/view/adminhtml/web/file/' . $fileName;
        $directoryRead    = $this->readFactory->create($moduleDir);
        $filePath         = $directoryRead->getRelativePath($fileAbsolutePath);
        if (!$directoryRead->isFile($filePath)) {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $this->messageManager->addErrorMessage(__('There is no sample file for this entity.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/import');
            return $resultRedirect;
        }
        $fileSize = isset($directoryRead->stat($filePath)['size'])
            ? $directoryRead->stat($filePath)['size'] : null;

        $this->fileFactory->create(
            $fileName,
            null,
            DirectoryList::VAR_DIR,
            'application/octet-stream',
            $fileSize
        );
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setContents($directoryRead->readFile($filePath));
        return $resultRaw;
    }
}