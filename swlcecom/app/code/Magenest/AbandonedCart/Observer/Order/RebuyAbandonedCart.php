<?php

namespace Magenest\AbandonedCart\Observer\Order;

use Magenest\AbandonedCart\Model\Config\Source\Mail as EmailStatus;

class RebuyAbandonedCart implements \Magento\Framework\Event\ObserverInterface
{
    protected $_logContentFactory;

    protected $_ruleFactory;

    protected $_abandonedCartFactory;

    /**
     * RebuyAbandonedCart constructor.
     *
     * @param \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory
     * @param \Magenest\AbandonedCart\Model\RuleFactory $ruleFactory
     * @param \Magenest\AbandonedCart\Model\AbandonedCartFactory $abandonedCartFactory
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\LogContentFactory $logContentFactory,
        \Magenest\AbandonedCart\Model\RuleFactory $ruleFactory,
        \Magenest\AbandonedCart\Model\AbandonedCartFactory $abandonedCartFactory
    ) {
        $this->_logContentFactory    = $logContentFactory;
        $this->_ruleFactory          = $ruleFactory;
        $this->_abandonedCartFactory = $abandonedCartFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if ($order->getState() === \Magento\Sales\Model\Order::STATE_NEW) {
            $ruleIds = $this->_ruleFactory->create()->getCollection()->getAllIds();
            if (!empty($ruleIds)) {
                $logContentModels = $this->_logContentFactory->create()->getCollection()
                    ->addFieldToFilter('status', EmailStatus::STATUS_QUEUED)
                    ->addFieldToFilter('rule_id', ['IN' => $ruleIds])
                    ->addFieldToFilter('quote_id', $order->getQuoteId());
                /** @var \Magenest\AbandonedCart\Model\LogContent $logContent */
                foreach ($logContentModels as $logContent) {
                    if ($logContent->getId()) {
                        $logContent->addData([
                            'status'     => EmailStatus::STATUS_CANCELLED,
                            'log'        => 'Customer Re-bought Abandoned Cart',
                            'is_restore' => 2
                        ]);
                        $logContent->save();
                    }
                }
                $abandonedCartModels = $this->_abandonedCartFactory->create()->getCollection()->addFieldToFilter(
                    'quote_id', $order->getQuoteId()
                );
                foreach ($abandonedCartModels as $abandonedCartModel) {
                    $abandonedCartModel->setPlaced($order->getId())->save();
                }
            }
        }
    }
}