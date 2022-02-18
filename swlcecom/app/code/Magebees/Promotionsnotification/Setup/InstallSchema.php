<?php
namespace Magebees\Promotionsnotification\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $table = $installer->getConnection()
            ->newTable($installer->getTable('magebees_promotionsnotification'))
            ->addColumn(
                'notification_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true,'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Notification ID'
            )
            ->addColumn('title', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('notification_content', Table::TYPE_TEXT, '2M', ['nullable' => false])
            ->addColumn('notification_style', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('background_color', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('from_date', Table::TYPE_DATETIME, null, ['nullable' => true, 'default' => null])
            ->addColumn('to_date', Table::TYPE_DATETIME, null, ['nullable' => true, 'default' => null])
            ->addColumn('sort_order', Table::TYPE_SMALLINT, 6, ['nullable' => false])
            ->addColumn('status', Table::TYPE_BOOLEAN, ['nullable' => false])
            ->addColumn('cart_page', Table::TYPE_BOOLEAN, ['nullable' => false])
            ->addColumn('unique_code', Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => null])
            ->setComment('Magebees Notification Details');

        $installer->getConnection()->createTable($table);
        
        $table = $installer->getConnection()
            ->newTable($installer->getTable('magebees_notification_page'))
            ->addColumn(
                'notification_page_id',
                Table::TYPE_INTEGER,
                null,
                [
                'identity' => true,'unsigned' => true, 'nullable' => false, 'primary' => true
                ]
            )
            ->addColumn('notification_id', Table::TYPE_INTEGER, 10, ['nullable' => false,'unsigned' => true])
            ->addColumn('pages', Table::TYPE_INTEGER, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName('IDX_NOTIFICATION_PAGE_ID', ['notification_id']),
                ['notification_id']
            )
            ->addForeignKey(
                $installer->getFkName(
                    'magebees_notification_page',
                    'notification_id',
                    'magebees_promotionsnotification',
                    'notification_id'
                ),
                'notification_id',
                $installer->getTable('magebees_promotionsnotification'),
                'notification_id',
                Table::ACTION_CASCADE,
                Table::ACTION_CASCADE
            )
            ->setComment('Finder To Pages ids Relations');

        $installer->getConnection()->createTable($table);
        
        $table = $installer->getConnection()
            ->newTable($installer->getTable('magebees_notification_category'))
            ->addColumn(
                'notification_category_id',
                Table::TYPE_INTEGER,
                null,
                [
                'identity' => true,'unsigned' => true, 'nullable' => false, 'primary' => true
                ]
            )
            ->addColumn('notification_id', Table::TYPE_INTEGER, 10, ['nullable' => false,'unsigned' => true])
            ->addColumn('category_ids', Table::TYPE_INTEGER, null, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName('IDX_NOTIFICATION_CATEGORY_NOTIFICATION_ID', ['notification_id']),
                ['notification_id']
            )
            ->addForeignKey(
                $installer->getFkName(
                    'magebees_notification_category',
                    'notification_id',
                    'magebees_promotionsnotification',
                    'notification_id'
                ),
                'notification_id',
                $installer->getTable('magebees_promotionsnotification'),
                'notification_id',
                Table::ACTION_CASCADE,
                Table::ACTION_CASCADE
            )
            ->setComment('Notification To Category ids Relations');
         
        $installer->getConnection()->createTable($table);
        
        $table = $installer->getConnection()
            ->newTable($installer->getTable('magebees_notification_product'))
            ->addColumn(
                'notification_product_id',
                Table::TYPE_INTEGER,
                null,
                [
                'identity' => true,'unsigned' => true, 'nullable' => false, 'primary' => true
                ]
            )
            ->addColumn('notification_id', Table::TYPE_INTEGER, 10, ['nullable' => false,'unsigned' => true])
            ->addColumn('product_sku', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName('IDX_NOTIFICATION_PRODUCT_NOTIFICATION_ID', ['notification_id']),
                ['notification_id']
            )
            ->addForeignKey(
                $installer->getFkName(
                    'magebees_notification_product',
                    'notification_id',
                    'magebees_promotionsnotification',
                    'notification_id'
                ),
                'notification_id',
                $installer->getTable('magebees_promotionsnotification'),
                'notification_id',
                Table::ACTION_CASCADE,
                Table::ACTION_CASCADE
            )
            ->setComment('Notification To Product SKUs Relations');

        $installer->getConnection()->createTable($table);
        
        $table = $installer->getConnection()
            ->newTable($installer->getTable('magebees_notification_store'))
            ->addColumn(
                'notification_store_id',
                Table::TYPE_INTEGER,
                null,
                [
                'identity' => true,'unsigned' => true, 'nullable' => false, 'primary' => true
                ]
            )
            ->addColumn('notification_id', Table::TYPE_INTEGER, 10, ['nullable' => false,'unsigned' => true])
            ->addColumn('store_ids', Table::TYPE_SMALLINT, 6, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName('IDX_NOTIFICATION_STORE_NOTIFICATION_ID', ['notification_id']),
                ['notification_id']
            )
            ->addForeignKey(
                $installer->getFkName(
                    'magebees_notification_store',
                    'notification_id',
                    'magebees_promotionsnotification',
                    'notification_id'
                ),
                'notification_id',
                $installer->getTable('magebees_promotionsnotification'),
                'notification_id',
                Table::ACTION_CASCADE,
                Table::ACTION_CASCADE
            )
            ->setComment('Notification To Store IDs Relations');

        $installer->getConnection()->createTable($table);
        
        $table = $installer->getConnection()
            ->newTable($installer->getTable('magebees_notification_customer'))
            ->addColumn(
                'notification_customer_id',
                Table::TYPE_INTEGER,
                null,
                [
                'identity' => true,'unsigned' => true, 'nullable' => false, 'primary' => true
                ]
            )
            ->addColumn('notification_id', Table::TYPE_INTEGER, 10, ['nullable' => false,'unsigned' => true])
            ->addColumn('customer_ids', Table::TYPE_SMALLINT, 6, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName('IDX_NOTIFICATION_STORE_NOTIFICATION_ID', ['notification_id']),
                ['notification_id']
            )
            ->addForeignKey(
                $installer->getFkName(
                    'magebees_notification_customer',
                    'notification_id',
                    'magebees_promotionsnotification',
                    'notification_id'
                ),
                'notification_id',
                $installer->getTable('magebees_promotionsnotification'),
                'notification_id',
                Table::ACTION_CASCADE,
                Table::ACTION_CASCADE
            )
            ->setComment('Notification To Store IDs Relations');

        $installer->getConnection()->createTable($table);
            
        $installer->endSetup();
    }
}
