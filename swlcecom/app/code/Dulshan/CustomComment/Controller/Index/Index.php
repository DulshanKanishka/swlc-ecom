<?php
namespace Dulshan\CustomComment\Controller\Index;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Index extends Action
{
    public function __construct(
        CheckoutSession $checkoutSession,
        Context $context
    ) {
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $quote = $this->checkoutSession->getQuote();

        $comment = $quote->getCustomComment();
        if ($comment && !$data) {
            $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
            $result->setData(['comment'=>$comment]);
            return $result;
        }

        if (isset($data)) {
            if (!$data) {
                $data['message'] = "";
            }
            $msg = $data['message'];
        }
        if (isset($msg)) {
            $quote->setCustomComment($msg);
            $quote->save();
        }
        if ((!$comment && !$data) || (!$comment && $data['message'] == "")) {
            $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
            $result->setData(['comment'=>""]);
            return $result;
        }
    }
}
