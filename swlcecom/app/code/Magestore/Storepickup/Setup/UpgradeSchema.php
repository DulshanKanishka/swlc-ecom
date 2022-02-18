<?php

/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_StorePickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magestore\Storepickup\Setup\InstallSchema as StorepickupShema;

/**
 *
 *
 * @category Magestore
 * @package  Magestore_Pdfinvoiceplus
 * @module   Pdfinvoiceplus
 * @author   Magestore Developer
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '0.1.1', '<')) {
            $this->changeColumnImage($setup);
        }
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->addOwnerInformation($setup);
        }

        if (version_compare($context->getVersion(), '1.1.1', '<')) {
            $this->addWarehouseToStoreAndOrder($setup);
        }

        if (version_compare($context->getVersion(), '1.1.2', '<')) {
            $this->addWarehouseIdColumnToOrder($setup);
        }

        if (version_compare($context->getVersion(), '1.1.3', '<')) {
            $this->updateShippingDescriptionTextLength($setup);
        }

        $installer->endSetup();
    }

    /**
     *
     * rename column storepickup_id in table magestore_storepickup_image to pickup_id
     *
     * @param SchemaSetupInterface $setup
     */
    public function changeColumnImage(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->dropForeignKey(
            $setup->getTable(StorepickupShema::SCHEMA_IMAGE),
            $setup->getFkName(
                StorepickupShema::SCHEMA_IMAGE,
                'storepickup_id',
                StorepickupShema::SCHEMA_STORE,
                'storepickup_id'
            )
        );

        $setup->getConnection()->dropIndex(
            $setup->getTable(StorepickupShema::SCHEMA_IMAGE),
            $setup->getIdxName(
                $setup->getTable(StorepickupShema::SCHEMA_IMAGE),
                ['storepickup_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            )
        );

        $setup->getConnection()->changeColumn(
            $setup->getTable(StorepickupShema::SCHEMA_IMAGE),
            'storepickup_id',
            'pickup_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => null,
                'comment' => 'StorePickup Id',
                'unsigned' => true
            ]
        );

        $setup->getConnection()->addIndex(
            $setup->getTable(StorepickupShema::SCHEMA_IMAGE),
            $setup->getIdxName(
                $setup->getTable(StorepickupShema::SCHEMA_IMAGE),
                ['pickup_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['pickup_id'],
            AdapterInterface::INDEX_TYPE_INDEX
        );

        $setup->getConnection()->addForeignKey(
            $setup->getFkName(
                StorepickupShema::SCHEMA_IMAGE,
                'pickup_id',
                StorepickupShema::SCHEMA_STORE,
                'storepickup_id'
            ),
            $setup->getTable(StorepickupShema::SCHEMA_IMAGE),
            'pickup_id',
            $setup->getTable(StorepickupShema::SCHEMA_STORE),
            'storepickup_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );

    }
    public function addOwnerInformation(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable(StorepickupShema::SCHEMA_STORE),
            'owner_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT
        );
        $setup->getConnection()->addColumn(
            $setup->getTable(StorepickupShema::SCHEMA_STORE),
            'owner_email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT
        );
        $setup->getConnection()->addColumn(
            $setup->getTable(StorepickupShema::SCHEMA_STORE),
            'owner_phone',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT
        );
    }

    public function addWarehouseToStoreAndOrder(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable(StorepickupShema::SCHEMA_STORE),
            'warehouse_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'storepickup_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'storepickup_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'storepickup_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME
        );
    }

    public function addWarehouseIdColumnToOrder(SchemaSetupInterface $setup)
    {
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'warehouse_id')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                'warehouse_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER
            );
        }
        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order_grid'), 'warehouse_id')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order_grid'),
                'warehouse_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER
            );
        }
    }

    public function updateShippingDescriptionTextLength(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->changeColumn(
            $setup->getTable('sales_order'),
            'shipping_description',
            'shipping_description',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Shipping Description'
            ]
        );
    }
}