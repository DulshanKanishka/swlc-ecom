<?php

namespace Sunflowerbiz\CategoryPassword\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
  $tableName = $installer->getTable('category_password');
        // Check if the table already exists
        if ($installer->getConnection()->isTableExists($tableName) != true) {


            // Create tutorial_simplenews table
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'record id'
                )
                
                ->addColumn(
                    'category_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false,
                     'unsigned' => true ],
                    'Customer Entity'
                )
                ->addColumn(
                    'password',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Multi Use Token'
                )
                
              ;
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
} //sun_vault/Setup/InstallSchema.php