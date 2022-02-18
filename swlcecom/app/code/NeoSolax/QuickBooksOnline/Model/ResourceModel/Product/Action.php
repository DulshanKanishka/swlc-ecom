<?php

namespace NeoSolax\QuickBooksOnline\Model\ResourceModel\Product;

use Magenest\QuickBooksOnline\Model\QueueFactory;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Factory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\TypeTransitionManager;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Model\Entity\Attribute\UniqueValidationInterface;
use Magento\Eav\Model\Entity\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;

class Action extends \Magento\Catalog\Model\ResourceModel\Product\Action
{
    private $dateTime;
    private $productCollectionFactory;
    private $typeTransitionManager;
    private $typeIdValuesToSave = [];

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Factory $modelFactory,
        QueueFactory $queueFactory,
        UniqueValidationInterface $uniqueValidator,
        DateTime $dateTime,
        ProductCollectionFactory $productCollectionFactory,
        TypeTransitionManager $typeTransitionManager,
        $data = []
    ) {
        $this->queueFactory = $queueFactory;
        $this->dateTime = $dateTime;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->typeTransitionManager = $typeTransitionManager;
        $this->type     = 'item';
        parent::__construct($context, $storeManager, $modelFactory, $uniqueValidator, $dateTime, $productCollectionFactory, $typeTransitionManager, $data);
    }

    public function updateAttributes($entityIds, $attrData, $storeId)
    {
        $object = new DataObject();
        $object->setStoreId($storeId);

        $attrData[ProductInterface::UPDATED_AT] = $this->dateTime->gmtDate();
        $this->getConnection()->beginTransaction();
        try {
            foreach ($attrData as $attrCode => $value) {
                if ($attrCode === ProductAttributeInterface::CODE_HAS_WEIGHT) {
                    $this->updateHasWeightAttribute($entityIds, $value);
                    continue;
                }

                $attribute = $this->getAttribute($attrCode);
                if (!$attribute->getAttributeId()) {
                    continue;
                }

                $i = 0;
                foreach ($entityIds as $entityId) {
                    $i++;
                    $object->setId($entityId);
                    $object->setEntityId($entityId);
                    // collect data for save
                    $this->_saveAttributeValue($object, $attribute, $value);
                    // save collected data every 1000 rows
                    if ($i % 1000 == 0) {
                        $this->_processAttributeValues();
                    }
                }
                $this->_processAttributeValues();
            }
            $this->getConnection()->commit();
            foreach ($entityIds as $id) {
                $this->addToQueue($id);
            }
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            throw $e;
        }

        return $this;
    }

    private function updateHasWeightAttribute($entityIds, $value): void
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addIdFilter($entityIds);
        // Type can be changed depending on weight only between simple and virtual products
        $productCollection->addFieldToFilter(
            Product::TYPE_ID,
            [
                'in' => [
                    Type::TYPE_SIMPLE,
                    Type::TYPE_VIRTUAL
                ]
            ]
        );
        $productCollection->addFieldToSelect(Product::TYPE_ID);
        $i = 0;

        foreach ($productCollection->getItems() as $product) {
            $product->setData(ProductAttributeInterface::CODE_HAS_WEIGHT, $value);
            $oldTypeId = $product->getTypeId();
            $this->typeTransitionManager->processProduct($product);

            if ($oldTypeId !== $product->getTypeId()) {
                $i++;
                $this->saveTypeIdValue($product);

                // save collected data every 1000 rows
                if ($i % 1000 === 0) {
                    $this->processTypeIdValues();
                }
            }
        }

        $this->processTypeIdValues();
    }

    private function saveTypeIdValue($product): self
    {
        $typeId = $product->getTypeId();

        if (!array_key_exists($typeId, $this->typeIdValuesToSave)) {
            $this->typeIdValuesToSave[$typeId] = [];
        }

        $this->typeIdValuesToSave[$typeId][] = $product->getId();

        return $this;
    }

    private function processTypeIdValues(): self
    {
        $connection = $this->getConnection();
        $table = $this->getTable('catalog_product_entity');

        foreach ($this->typeIdValuesToSave as $typeId => $entityIds) {
            $connection->update(
                $table,
                ['type_id' => $typeId],
                ['entity_id IN (?)' => $entityIds]
            );
        }
        $this->typeIdValuesToSave = [];

        return $this;
    }
    public function addToQueue($id)
    {
        $collection = $this->queueFactory->create()->getCollection();
        $data = [
            'type' => $this->type,
            'type_id' => $id
        ];
        $model = $collection->addFieldToFilter('type', $this->type)
            ->addFieldToFilter('type_id', $id)
            ->getFirstItem();

        $model->addData($data);
        $model->save();
    }
}
