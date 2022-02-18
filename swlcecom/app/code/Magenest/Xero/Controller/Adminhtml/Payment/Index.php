<?php
namespace Magenest\Xero\Controller\Adminhtml\Payment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Helper\Data as PaymentHelper;

/**
 * Class Index
 * @package Magenest\Xero\Controller\Adminhtml\Xero\Payment
 */
class Index extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $paymentHelper;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        PaymentHelper $paymentHelper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->paymentHelper = $paymentHelper;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magenest_Xero::xero');
        $resultPage->getConfig()->getTitle()->prepend(__('Payment Mapping'));

        return $resultPage;

    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::mapping');
    }
}
