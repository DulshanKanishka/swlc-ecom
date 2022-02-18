<?php
/**
 * Copyright © 2015 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 *
 *
 * Magenest_Xero extension
 * NOTICE OF LICENSE
 *
 * @category Magenest
 * @package  Magenest_Xero
 * @author   <ThaoPV> thaopw@gmail.com
 */
namespace Magenest\Xero\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\SetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema
 *
 * @package Magenest\Xero\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    
    /**@#+
     * Table prefix
     */
    const MODULE_TABLE_PREFIX = 'magenest_';


    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $this->createXeroLogTable($installer);
        $this->createXeroQueueTable($installer);

        $installer->endSetup();
    }

    /**
     * Create the table magenest_xero_log
     *
     * @param SetupInterface $installer
     */
    private function createXeroLogTable(SetupInterface $installer)
    {
        $tableName = self::MODULE_TABLE_PREFIX . 'xero_log';
        if ($installer->tableExists($tableName)) {
            $installer->getConnection()->dropTable($tableName);
        }

        $table = $installer->getConnection()->newTable(
            $installer->getTable($tableName)
        )->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ],
            'Id'
        )->addColumn(
            'type',
            Table::TYPE_TEXT,
            45,
            ['nullable' => false],
            'Entity Type'
        )->addColumn(
            'entity_id',
            Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Entity Id'
        )->addColumn(
            'dequeue_time',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => false],
            'Sync time'
        )->addColumn(
            'status',
            Table::TYPE_SMALLINT,
            null,
            ['nullable' => false],
            'Status'
        )->addColumn(
            'xero_id',
            Table::TYPE_TEXT,
            55,
            ['nullable' => true],
            'Xero Id'
        )->addColumn(
            'msg',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Message'
        )->setComment(
            'Sync Log Table'
        );
        
        $installer->getConnection()->createTable($table);
    }

    /**
     * Create the table magenest_xero_queue
     *
     * @param SetupInterface $installer
     */
    private function createXeroQueueTable(SetupInterface $installer)
    {
        $tableName = self::MODULE_TABLE_PREFIX . 'xero_queue';
        $table = $installer->getConnection()->newTable(
            $installer->getTable($tableName)
        )->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ],
            'Id'
        )->addColumn(
            'type',
            Table::TYPE_TEXT,
            45,
            ['nullable' => true],
            'Entity Type'
        )->addColumn(
            'entity_id',
            Table::TYPE_TEXT,
            null,
            ['nullable' => false],
            'Entity Id'
        )->addColumn(
            'enqueue_time',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => true],
            'Enqueue Time'
        )->addColumn(
            'priority',
            Table::TYPE_SMALLINT,
            null,
            ['nullable' => false],
            'Enqueue Time'
        )->setComment(
            'Xero Sync Queue'
        );
        
        $installer->getConnection()->createTable($table);
    }
}
