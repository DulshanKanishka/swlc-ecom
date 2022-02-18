<?php

namespace NeoSolax\CustomComment\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        // Version of module in setup table is less then the give value.
        if (version_compare($context->getVersion(), '2.0.1') < 0) {

            // get table customer_entity
            $eavTable1 = $setup->getTable('quote_item');
            $eavTable2 = $setup->getTable('sales_order_item');

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($eavTable1) == true) {
                $connection = $setup->getConnection();

                $connection->dropColumn($eavTable1, 'custom_comment');
            }
            if ($setup->getConnection()->isTableExists($eavTable2) == true) {
                $connection = $setup->getConnection();

                $connection->dropColumn($eavTable2, 'custom_comment');
            }
        }

        $setup->startSetup();

        $quote = 'quote';
        $orderTable = 'sales_order';

        $setup->getConnection()
            ->addColumn(
                $setup->getTable($quote),
                'custom_comment',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Custom Comment'
                ]
            );
        //Order table
        $setup->getConnection()
            ->addColumn(
                $setup->getTable($orderTable),
                'custom_comment',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Custom Comment'
                ]
            );

        $setup->endSetup();


    }
}
