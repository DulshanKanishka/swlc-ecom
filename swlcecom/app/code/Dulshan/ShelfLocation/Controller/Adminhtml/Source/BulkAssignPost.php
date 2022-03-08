<?php
namespace Dulshan\ShelfLocation\Controller\Adminhtml\Source;

use Magento\AsynchronousOperations\Model\MassSchedule;
use Magento\Backend\App\Action;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Controller\ResultFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryCatalogAdminUi\Model\BulkOperationsConfig;
use Magento\InventoryCatalogAdminUi\Model\BulkSessionProductsStorage;
use Magento\InventoryCatalogApi\Api\BulkSourceAssignInterface;
use Psr\Log\LoggerInterface;

class BulkAssignPost extends \Magento\InventoryCatalogAdminUi\Controller\Adminhtml\Source\BulkAssignPost
{
    private $bulkSessionProductsStorage;
    private $bulkSourceAssign;
    private $massSchedule;
    private $authSession;
    private $bulkOperationsConfig;
    private $logger;

    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Action\Context $context,
        BulkSourceAssignInterface $bulkSourceAssign,
        BulkSessionProductsStorage $bulkSessionProductsStorage,
        BulkOperationsConfig $bulkOperationsConfig,
        MassSchedule $massSchedule,
        LoggerInterface $logger
    ) {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->bulkSessionProductsStorage = $bulkSessionProductsStorage;
        $this->bulkSourceAssign = $bulkSourceAssign;
        $this->massSchedule = $massSchedule;
        $this->authSession = $context->getAuth();
        $this->bulkOperationsConfig = $bulkOperationsConfig;
        $this->logger = $logger;
        parent::__construct($context, $bulkSourceAssign, $bulkSessionProductsStorage, $bulkOperationsConfig, $massSchedule, $logger);
    }

    private function runSynchronousOperation(array $skus, array $sourceCodes): void
    {
        $count = $this->bulkSourceAssign->execute($skus, $sourceCodes);
        $this->messageManager->addSuccessMessage(__('Bulk operation was successful: %count assignments.', [
            'count' => $count
        ]));
    }

    private function runAsynchronousOperation(array $skus, array $sourceCodes): void
    {
        $batchSize = $this->bulkOperationsConfig->getBatchSize();
        $userId = (int) $this->authSession->getUser()->getId();

        $skusChunks = array_chunk($skus, $batchSize);
        $operations = [];
        foreach ($skusChunks as $skuChunk) {
            $operations[] = [
                'skus' => $skuChunk,
                'sourceCodes' => $sourceCodes,
            ];
        }

        $this->massSchedule->publishMass(
            'async.V1.inventory.bulk-product-source-assign.POST',
            $operations,
            null,
            $userId
        );

        $this->messageManager->addSuccessMessage(__('Your request was successfully queued for asynchronous execution'));
    }

    public function execute()
    {
        $sourceCodes = $this->getRequest()->getParam('sources', []);
        $skus = $this->bulkSessionProductsStorage->getProductsSkus();

        $sourcecodes = $this->getRequest()->getParam('sourcescodes');

        foreach ($skus as $sku) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(SourceItemInterface::SKU, $sku)
                ->create();
            $result = $this->sourceItemRepository->getList($searchCriteria)->getItems();
            foreach ($result as $value) {
                foreach ($sourcecodes as $sourcecode) {
                    $shelflocation = $this->getRequest()->getParam($sourcecode);
                    if ($shelflocation && $value->getSourceCode() == $sourcecode) {
                        $value->setShelfLocation($shelflocation);
                        $value->save();
                    }
                }
            }
        }
        $async = $this->bulkOperationsConfig->isAsyncEnabled();

        try {
            if ($async) {
                $this->runAsynchronousOperation($skus, $sourceCodes);
            } else {
                $this->runSynchronousOperation($skus, $sourceCodes);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage(__('Something went wrong during the operation.'));
        }

        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $result->setPath('catalog/product/index');
    }
}
