<?php
namespace Magenest\Xero\Controller\Adminhtml\Sync;

use Magenest\Xero\Model\Synchronization;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magenest\Xero\Model\Helper;

/**
 * Class Product
 * @package Magenest\Xero\Controller\Adminhtml\Sync
 */
class Product extends \Magento\Backend\App\Action
{
    /**
     * @var Synchronization\Item
     */
    protected $syncItem;

    /**
     * @var Synchronization\BankTransaction
     */
    protected $syncBankTransaction;

    protected $collectionFactory;

    protected $xeroHelper;

    /**
     * Product constructor.
     * @param Context $context
     * @param Synchronization\Item $syncItem
     * @param Synchronization\BankTransaction $syncBankTransaction
     * @param CollectionFactory $collectionFactory
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        Synchronization\Item $syncItem,
        Synchronization\BankTransaction $syncBankTransaction,
        CollectionFactory $collectionFactory,
        Helper $helper
    ) {
        $this->syncBankTransaction = $syncBankTransaction;
        $this->syncItem = $syncItem;
        $this->collectionFactory = $collectionFactory;
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
            $productId = $this->getRequest()->getParam('id');

            $collection = $this->collectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter("entity_id", ["IN" => $productId]);

            /** @var \Magento\Catalog\Model\Product $product */
            $product = $collection->load()->getFirstItem();
            if (!$this->xeroHelper->isXeroConnectedByIds($productId, $this->collectionFactory, 'entity_id')) {
                return $resultRedirect;
            }
            if ($product->getId()) {
                foreach ($product->getWebsiteIds() as $websiteId) {
                    if ($this->xeroHelper->isMultipleWebsiteEnable()) {
                        $this->xeroHelper->setScope('websites');
                        $this->xeroHelper->setScopeId($websiteId);
                    }

                    /** sync customers */
                    $xml = $this->syncItem->addRecord($product);
                    $xml = '<Items>' . $xml . '</Items>';
                    $this->syncItem->syncData($xml);
                    $transactionXml = $this->syncBankTransaction->addRecord($product);
                    $transactionXml = $this->syncBankTransaction->addOtherTags($transactionXml);
                    $this->syncBankTransaction->syncData($transactionXml);

                    if (!$this->xeroHelper->isMultipleWebsiteEnable()) {
                        break;
                    }
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
