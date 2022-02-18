<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Blacklist;

class Save extends \Magenest\AbandonedCart\Controller\Adminhtml\Blacklist
{
    protected $csv;

    public function __construct(
        \Magento\Framework\File\Csv $csv,
        \Magenest\AbandonedCart\Model\BlackListFactory $blacklistFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->csv = $csv;
        parent::__construct($blacklistFactory, $logger, $registry, $pageFactory, $context);
    }

    public function execute()
    {
        try {
            $count = 0;
            $file = $this->getRequest()->getFiles();
            if (isset($file)) {
                $blacklist = $file->getArrayCopy();
                if (is_array($blacklist)&&isset($blacklist['blacklist'])) {
                    $count = $this->import($blacklist['blacklist']);
                }
            }
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been inserted.', $count)
            );
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }

    public function import($file)
    {
        if (!isset($file['tmp_name']) || $file['tmp_name'] == "")
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid file upload attempt.'));
        if ($file['type'] != 'text/csv') {
            throw new \Magento\Framework\Exception\LocalizedException(__('You must upload file csv.'));
        }
        $csvData = $this->csv->getData($file['tmp_name']);
        $count   = 0;
        $data    = 1;
        $records = [];
        $columns = $csvData[0];
        foreach ($columns as $key => $value) {
            if ($value == 'address') {
                $data = $key;
            }
        }
        foreach ($csvData as $key => $csv) {
            if ($count == 0) {
                $count++;
                continue;
            }
            $records[] = [
                'address' => $csvData[$key][$data]
            ];
            $count++;
        }
        /** @var \Magenest\AbandonedCart\Model\BlackList $blacklistModel */
        $blacklistModel = $this->_blacklistFactory->create();
        $blacklistModel->insertMultiple($records);
        return $count > 0 ? ($count - 1) : 0;
    }
}