<?php
namespace Magenest\Xero\Controller\Adminhtml\Sync;

use Magenest\Xero\Model\Synchronization;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory;
use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\Config;

/**
 * Class Credit
 * @package Magenest\Xero\Controller\Adminhtml\Sync
 */
class Credit extends \Magento\Backend\App\Action
{
    /**
     * @var Synchronization\CreditNote
     */
    protected $syncCredit;

    protected $collectionFactory;

    protected $xeroHelper;

    /**
     * Credit constructor.
     * @param Context $context
     * @param Synchronization\CreditNote $syncCredit
     * @param CollectionFactory $collectionFactory
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        Synchronization\CreditNote $syncCredit,
        CollectionFactory $collectionFactory,
        Helper $helper
    ) {
        $this->syncCredit = $syncCredit;
        $this->collectionFactory = $collectionFactory;
        $this->xeroHelper = $helper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        try {
            $creditId = $this->getRequest()->getParam('id');
            $collection = $this->collectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter("entity_id", ["IN" => $creditId]);

            /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
            $creditmemo = $collection->load()->getFirstItem();
            if ($creditId) {
                if ($this->xeroHelper->isMultipleWebsiteEnable()) {
                    $this->xeroHelper->setScope('websites');
                    $this->xeroHelper->setScopeId($creditmemo->getStore()->getWebsiteId());
                }
                if (!$this->xeroHelper->getConfig(Config::XML_PATH_XERO_IS_CONNECTED)) {
                    $this->messageManager->addErrorMessage('Please connect the integration to your Xero account first!');
                    return $resultRedirect;
                }
                $xml = $this->syncCredit->addRecord($creditmemo);
                $xml = '<CreditNotes>' . $xml . '</CreditNotes>';
                $this->syncCredit->syncAllGuestToXero();
                $this->syncCredit->syncData($xml);
                $this->syncCredit->syncRefundXml($creditmemo);
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
