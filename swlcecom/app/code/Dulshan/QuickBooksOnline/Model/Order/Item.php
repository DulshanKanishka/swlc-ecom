<?php

namespace Dulshan\QuickBooksOnline\Model\Order;

use Magento\Framework\Api\AttributeValueFactory;

class Item extends \Magento\Sales\Model\Order\Item
{
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $orderFactory, $storeManager, $productRepository, $resource, $resourceCollection, $data, $serializer);
    }

    public function getSimpleQtyToShip()
    {
        $qtyrefurnded = $this->getQtyRefunded() - $this->getQtyBack();
        $qty = $this->getQtyOrdered() - $this->getQtyShipped() - $this->getQtyCanceled() - $qtyrefurnded - $this->getQtyReservation();
        return max(round($qty, 8), 0);
    }
}
