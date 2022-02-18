<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Abandonedcart;

use Magenest\AbandonedCart\Helper\MandrillConnector;
use Magenest\AbandonedCart\Helper\SendMail;
use Magenest\AbandonedCart\Model\LogContent;

class SendEmail extends \Magenest\AbandonedCart\Controller\Adminhtml\Abandonedcart
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
                    ->addFieldToFilter('type', 'Email')
                    ->addFieldToFilter('status', ['neq' => 2])
                    ->getFirstItem();
                if ($this->_mandrillConnector->isEnable()) {
                    $this->_mandrillConnector->sendEmails([$logContentModel]);
                } else {
                    $this->_sendMailHelper->send($logContentModel);
                }
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_logger->critical($exception->getMessage());
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}