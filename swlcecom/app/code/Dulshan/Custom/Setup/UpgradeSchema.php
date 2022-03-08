<?php

namespace Dulshan\Custom\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.8', '<')) {
            $orderTableGrid = 'sales_order_grid';
            $orderTable = 'sales_order';
            $setup->getConnection()
                ->dropColumn(
                    $setup->getTable('sales_order_grid'),
                    'phone_number'
                );
            $setup->getConnection()
                ->dropColumn(
                    $setup->getTable('sales_order'),
                    'phone_number'
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($orderTableGrid),
                    'phone_number',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 15,
                        'comment' => 'Telephone Number'
                    ]
                );

            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($orderTable),
                    'phone_number',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 15,
                        'comment' => 'Telephone Number'
                    ]
                );
        }
        if (version_compare($context->getVersion(), '1.0.8', '<')) {
            $table = $setup->getTable('sales_order_grid');
            $connection = $setup->getConnection();
            $connection
                ->addIndex(
                    $table,
                    $setup->getIdxName(
                        $table,
                        ['phone_number','increment_id','billing_name','shipping_name','shipping_address','billing_address','customer_name','customer_email'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
                    ),
                    ['phone_number','increment_id','billing_name','shipping_name','shipping_address','billing_address','customer_name','customer_email'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
                );
        }
        if (version_compare($context->getVersion(), '1.0.8', '<')) {
            $table = $setup->getTable('sales_order_grid');
            $connection = $setup->getConnection();
            $connection
                ->dropIndex(
                    $table,
                    'FTI_65B9E9925EC58F0C7C2E2F6379C233E7'
                );
        }
        $setup->endSetup();
    }
}
