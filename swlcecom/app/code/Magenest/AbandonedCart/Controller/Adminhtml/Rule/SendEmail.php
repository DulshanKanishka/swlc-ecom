<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Rule;

use Magenest\AbandonedCart\Helper\MandrillConnector;
use Magenest\AbandonedCart\Helper\SendMail;
use Magenest\AbandonedCart\Model\LogContent;
use Magenest\AbandonedCart\Model\TestCampaign;

class SendEmail extends \Magenest\AbandonedCart\Controller\Adminhtml\Rule
{

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        try {
            if (isset($params['id']) && $params['id']) {
                $id              = $params['id'];
                $logContentModel = $this->_logContentFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('type', 'Campaign')
                    ->addFieldToFilter('id', $id)
                    ->getFirstItem();
                if ($this->_mandrillConnector->isEnable()) {
                    $this->_mandrillConnector->sendEmails($logContentModel);
                } else {
                    $this->_sendMailHelper->send($logContentModel);
                }
//                $campaignModel = $this->_testCampaignFactory->create()->load($id);
//                $sendDate = new \DateTime();
//                $sendDate->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
//                $campaignModel->setIsSend(2);
//                $campaignModel->setSentDate($sendDate);
//                $campaignModel->save();
                $this->messageManager->addSuccessMessage(__('You sent message to "%1" successfully.', $logContentModel->getData('recipient_adress')));
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_logger->critical($exception->getMessage());
        }
        return $this->resultRedirectFactory->create()->setPath(
            'abandonedcart/rule/edit',
            ['id' => $params['rule_id']]
        );
    }
}