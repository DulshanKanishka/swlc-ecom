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
namespace Bss\AutoCancelOrder\Helper;

use \Magento\Framework\Exception\LocalizedException;

class CancelOrderImplementation
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var \Bss\AutoCancelOrder\Model\CancelLogFactory
     */
    protected $log;

    /**
     * @var \Bss\AutoCancelOrder\Model\CancelLogRepository
     */
    protected $logRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * CancelOrderImplementation constructor.
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param Data $dataHelper
     * @param \Bss\AutoCancelOrder\Model\CancelLogFactory $log
     * @param \Bss\AutoCancelOrder\Model\CancelLogRepository $logRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagement
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        Data $dataHelper,
        \Bss\AutoCancelOrder\Model\CancelLogFactory $log,
        \Bss\AutoCancelOrder\Model\CancelLogRepository $logRepository,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->dataHelper = $dataHelper;
        $this->log = $log;
        $this->logRepository = $logRepository;
        $this->logger = $logger;
        $this->orderManagement = $orderManagement;
    }

    /**
     * Cancel orders
     *
     * @param array $cancelStatuses
     * @param string $cancelDate
     * @param array $cancelPaymentMethods
     * @throws LocalizedException
     * @return bool
     */
    public function processCancel($cancelStatuses, $cancelDate, $cancelPaymentMethods)
    {
        $fromDate = $this->dataHelper->convertConfigTimeToUtc($cancelDate);
        $orderCollection = $this->orderCollectionFactory
                                ->create()
                                ->addAttributeToFilter('status', ['in'=>$cancelStatuses])
                                ->addAttributeToFilter('created_at', ['from'=>$fromDate]);
        $orderCollection = $this->paymentMethodFilter($orderCollection, $cancelPaymentMethods);

        $checkSuccess = true;
        foreach ($orderCollection as $order) {
            try {
                $this->orderManagement->cancel($order->getId());
                $this->logResult('Order #' . $order->getIncrementId() . ' was successfully canceled!');
            } catch (\Exception $e) {
                $this->logger->info($e);
                $this->logResult(
                    'Order #' . $order->getIncrementId()
                    . ' wasn\'t canceled. Please check file log for more information!'
                );
                $checkSuccess = false;
            }
        }

        return $checkSuccess;
    }

    /**
     * Filter order by payment method
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection
     * @param array $cancelPaymentMethods
     * @return array
     */
    private function paymentMethodFilter($orderCollection, $cancelPaymentMethods)
    {
        $data = [];
        $cancelTimeCondition = 0;
        foreach ($orderCollection as $order) {
            $paymentMethod = $order->getPayment()->getMethod();
            $orderDate = strtotime($order->getCreatedAt());
            foreach ($cancelPaymentMethods as $method) {
                switch ($method['unit_id']) {
                    case "hour":
                        $cancelTimeCondition = $orderDate + $method['duration'] * 3600;
                        break;
                    case "day":
                        $cancelTimeCondition = $orderDate + $method['duration'] * 3600 * 24;
                        break;
                }

                if (($method['payment_method_group_id'] == $paymentMethod
                        || $method['payment_method_group_id'] == 'all_payment_method')
                    && $cancelTimeCondition < time()) {
                    array_push($data, $order);
                }
            }
        }
        return $data;
    }

    /**
     * Log cancel action
     *
     * @param string $content
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return void
     */
    private function logResult($content)
    {
        $log = $this->log->create();
        $log->setContent($content);
        $this->logRepository->save($log);
    }
}
