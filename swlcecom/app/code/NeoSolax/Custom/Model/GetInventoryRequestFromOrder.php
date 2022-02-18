<?php

namespace NeoSolax\Custom\Model;

use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestExtensionInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class GetInventoryRequestFromOrder extends \Magento\InventorySourceSelectionApi\Model\GetInventoryRequestFromOrder
{
    protected $addressInterfaceFactory;
    protected $inventoryRequestFactory;
    protected $inventoryRequestExtensionFactory;
    protected $orderRepository;
    protected $storeManager;
    protected $stockByWebsiteIdResolver;

    public function __construct(
        InventoryRequestInterfaceFactory $inventoryRequestFactory,
        InventoryRequestExtensionInterfaceFactory $inventoryRequestExtensionFactory,
        OrderRepositoryInterface $orderRepository,
        AddressInterfaceFactory $addressInterfaceFactory,
        StoreManagerInterface $storeManager,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
    ) {
        $this->inventoryRequestFactory = $inventoryRequestFactory;
        $this->inventoryRequestExtensionFactory = $inventoryRequestExtensionFactory;
        $this->orderRepository = $orderRepository;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->storeManager = $storeManager;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        parent::__construct($inventoryRequestFactory, $inventoryRequestExtensionFactory, $orderRepository, $addressInterfaceFactory, $storeManager, $stockByWebsiteIdResolver);
    }
    public function execute(int $orderId, array $requestItems): InventoryRequestInterface
    {
        $order = $this->orderRepository->get($orderId);

        $store = $this->storeManager->getStore($order->getStoreId());
        $stock = $this->stockByWebsiteIdResolver->execute((int)$store->getWebsiteId());

        $inventoryRequest = $this->inventoryRequestFactory->create([
            'stockId' => $stock->getStockId(),
            'items'   => $requestItems
        ]);

        $address = $this->getAddressFromOrder($order);
        if ($address !== null) {
            $extensionAttributes = $this->inventoryRequestExtensionFactory->create();
            $extensionAttributes->setDestinationAddress($address);
            $inventoryRequest->setExtensionAttributes($extensionAttributes);
        }

        return $inventoryRequest;
    }

    public function getAddressFromOrder(OrderInterface $order): ?AddressInterface
    {
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress === null) {
            return null;
        }

        return $this->addressInterfaceFactory->create([
            'country' => $shippingAddress->getCountryId(),
            'postcode' => $shippingAddress->getPostcode() ?? '',
            'street' => implode("\n", $shippingAddress->getStreet()),
            'region' => $shippingAddress->getRegion() ?? $shippingAddress->getRegionCode() ?? '',
            'city' => $shippingAddress->getCity()
        ]);
    }
}
