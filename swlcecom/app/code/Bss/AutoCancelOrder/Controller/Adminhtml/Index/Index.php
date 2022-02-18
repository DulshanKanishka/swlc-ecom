<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AutoCancelOrder
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AutoCancelOrder\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Bss\AutoCancelOrder\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Bss\AutoCancelOrder\Helper\CancelOrderImplementation
     */
    protected $cancelHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Bss\AutoCancelOrder\Helper\Data $dataHelper
     * @param \Bss\AutoCancelOrder\Helper\CancelOrderImplementation $cancelHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bss\AutoCancelOrder\Helper\Data $dataHelper,
        \Bss\AutoCancelOrder\Helper\CancelOrderImplementation $cancelHelper
    ) {
        $this->dataHelper = $dataHelper;
        $this->cancelHelper = $cancelHelper;
        parent::__construct($context);
        $this->messageManager = $context->getMessageManager();
    }

    /**
     * Execute manual cancel order
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if ($this->dataHelper->isEnabled()) {
            $cancelDate = $this->dataHelper->getCancelDate();
            $cancelStatuses = explode(",", $this->dataHelper->getActiveOrderStatus());
            $cancelPaymentMethods = $this->dataHelper->getActivePaymentMethod();

            $success = $this->cancelHelper->processCancel($cancelStatuses, $cancelDate, $cancelPaymentMethods);

            if ($success) {
                $this->messageManager->addSuccessMessage(__("Orders were successfully canceled!"));
            } else {
                $this->messageManager
                    ->addWarningMessage(
                        __("Some orders was not successfully canceled. Please check log file for more information!")
                    );
            }
        } else {
            $this->messageManager->addNoticeMessage(__("Module is disabled!"));
        }

        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }

    /**
     * Check right for controller
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bss_AutoCancelOrder::cancel_order');
    }
}
