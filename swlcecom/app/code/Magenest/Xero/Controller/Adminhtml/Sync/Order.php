<?php
namespace Magenest\Xero\Controller\Adminhtml\Sync;

use Magenest\Xero\Model\Synchronization;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\Config;
/**
 * Class Index
 * @package Magenest\Xero\Controller\Adminhtml\Sync
 */
class Order extends \Magento\Backend\App\Action
{
    /**
     * @var Synchronization\Order
     */
    protected $syncOrder;

    protected $collection;

    protected $xeroHelper;

    /**
     * Order constructor.
     * @param Context $context
     * @param Synchronization\Order $syncOrder
     * @param CollectionFactory $collectionFactory
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        Synchronization\Order $syncOrder,
        CollectionFactory $collectionFactory,
        Helper $helper
    ) {
        $this->syncOrder = $syncOrder;
        $this->collection = $collectionFactory;
        $this->xeroHelper = $helper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        try {
            $orderId = $this->getRequest()->getParam('id');
            $collection = $this->collection->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter("increment_id", ["IN" => $orderId]);
            /** @var \Magento\Sales\Model\Order $order */
            $order = $collection->load()->getFirstItem();
            if ($order->getIncrementId()) {
                if ($this->xeroHelper->isMultipleWebsiteEnable()) {
                    $this->xeroHelper->setScope('websites');
                    $this->xeroHelper->setScopeId($order->getStore()->getWebsiteId());
                }
                if (!$this->xeroHelper->getConfig(Config::XML_PATH_XERO_IS_CONNECTED)) {
                    $this->messageManager->addErrorMessage('Please connect the integration to your Xero account first!');
                    return $resultRedirect;
                }
                /** sync customers */
                $xml = $this->syncOrder->addRecord($order);
                if ($xml != '' && $xml != 'payment') {
                    $xml = '<Invoices>' . $xml . '</Invoices>';

                    $this->syncOrder->syncAllGuestToXero();
                    $this->syncOrder->syncData($xml);
                }
                if ($xml != '') {
                    $this->syncOrder->syncPayments();
                }
                $this->messageManager->addSuccess(
                    __('Sync process complete, please check out Logs for results')
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(
                __('Something happen during syncing process. Detail: ' . $e->getMessage())
            );
        }

        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::config_xero');
    }
}
