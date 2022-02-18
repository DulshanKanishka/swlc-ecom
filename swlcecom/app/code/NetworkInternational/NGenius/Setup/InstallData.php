<?php

namespace NetworkInternational\NGenius\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class InstallData
 */
class InstallData implements InstallDataInterface
{

    /**
     * n-genius State
     */
    const STATE = 'ngenius_state';

    /**
     * n-genius Status
     */
    const STATUS = [
        ['status' => 'ngenius_pending', 'label' => 'n-genius Pending'],
        ['status' => 'ngenius_processing', 'label' => 'n-genius Processing'],
        ['status' => 'ngenius_failed', 'label' => 'n-genius Failed'],
        ['status' => 'ngenius_complete', 'label' => 'n-genius Complete'],
        ['status' => 'ngenius_authorised', 'label' => 'n-genius Authorised'],
        ['status' => 'ngenius_fully_captured', 'label' => 'n-genius Fully Captured'],
        ['status' => 'ngenius_partially_captured', 'label' => 'n-genius Partially Captured'],
        ['status' => 'ngenius_fully_refunded', 'label' => 'n-genius Fully Refunded'],
        ['status' => 'ngenius_partially_refunded', 'label' => 'n-genius Partially Refunded'],
        ['status' => 'ngenius_auth_reversed', 'label' => 'n-genius Auth Reversed']
    ];

    /**
     * Install
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return null
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {

        $setup->startSetup();

        $setup->getConnection()->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], self::STATUS);

        $state[] = ['ngenius_pending', \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, '1', '1'];
        $state[] = ['ngenius_processing',\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, '0', '1'];
        $state[] = ['ngenius_failed', \Magento\Sales\Model\Order::STATE_CANCELED, '0', '1'];
        $state[] = ['ngenius_complete', \Magento\Sales\Model\Order::STATE_PROCESSING, '0', '1'];
        $state[] = ['ngenius_authorised', \Magento\Sales\Model\Order::STATE_PROCESSING, '0', '1'];
        $state[] = ['ngenius_fully_captured', \Magento\Sales\Model\Order::STATE_COMPLETE, '0', '1'];
        $state[] = ['ngenius_partially_captured', \Magento\Sales\Model\Order::STATE_PROCESSING, '0', '1'];
        $state[] = ['ngenius_fully_refunded', \Magento\Sales\Model\Order::STATE_CLOSED, '0', '1'];
        $state[] = ['ngenius_partially_refunded', \Magento\Sales\Model\Order::STATE_PROCESSING, '0', '1'];
        $state[] = ['ngenius_auth_reversed',\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW, '0', '1'];

        $setup->getConnection()->insertArray($setup->getTable('sales_order_status_state'), ['status', 'state', 'is_default', 'visible_on_front'], $state);

        $setup->endSetup();
    }
}