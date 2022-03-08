<?php

namespace Dulshan\QuickBooksOnline\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $orderTable = 'sales_order_item';
        $shipment = 'sales_shipment';

        //Order table
        if (version_compare($context->getVersion(), '1.0.6', '<')) {

            $setup->getConnection()->dropColumn($setup->getTable($orderTable), 'qty_back');
            $setup->getConnection()->dropColumn($setup->getTable($orderTable), 'qty_reservation');

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($orderTable),
                    'qty_back',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'Qty To Stock'
                    ]
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($orderTable),
                    'qty_reservation',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'Qty To Reservation'
                    ]
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($orderTable),
                    'qty_backres',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'Qty Back to Stock and Reservation'
                    ]
                );
            $setup->endSetup();
        }

        if (version_compare($context->getVersion(), '1.0.7', '<')) {
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($shipment),
                    'is_update',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'length' => null,
                        'default' => '0',
                        'comment' => 'Check shipment already updated'
                    ]
                );
            $setup->endSetup();
        }

    }
}
