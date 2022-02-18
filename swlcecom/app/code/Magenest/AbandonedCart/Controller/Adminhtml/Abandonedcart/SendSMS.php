<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Abandonedcart;

use Magenest\AbandonedCart\Model\Config\Source\Mail as EmailStatus;

class SendSMS extends \Magenest\AbandonedCart\Controller\Adminhtml\Abandonedcart
{

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        try {
            if (isset($params['id']) && $params['id']) {
                $id              = $params['id'];
                $logContentModel = $this->_logContentFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('abandonedcart_id', $id)
                    ->addFieldToFilter('type', 'SMS')
                    ->addFieldToFilter('status', ['neq' => 2])
                    ->getFirstItem();
                $respones        = $this->_sendSmsHelper->send($logContentModel);
                if ($respones['messages'][0]['status'] == "0") {
                    $logContentModel->addData(['status' => EmailStatus::STATUS_SENT, 'log' => 'Ok']);
                } else {
                    $logContentModel->addData(['status' => EmailStatus::STATUS_FAILED, 'log' => 'FAILED']);
                }
                $logContentModel->save();
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_logger->critical($exception->getMessage());
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}