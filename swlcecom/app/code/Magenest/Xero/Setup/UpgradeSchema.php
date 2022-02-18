<?php
namespace Magenest\Xero\Setup;

use Magento\Framework\Setup\SetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class UpgradeSchema
 *
 * @package Magenest\Xero\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * Upgrade database when run bin/magento setup:upgrade from command line
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '3.1.0') < 0) {
            $this->createXeroRequestTable($installer);
            $this->createXeroPaymentAccountMapping($installer);
            $this->createXeroTaxRatesMapping($installer);
        }
        if (version_compare($context->getVersion(), '3.1.3') < 0) {
            $this->createXeroXmlLogTable($installer);
            $this->addForeignKeyToLogTable($installer);
        }
        if (version_compare($context->getVersion(), '3.1.5') < 0) {
            $this->addScopeForXeroXmlLogTable($installer);
            $this->addScopeForTaxRateMappingTable($installer);
            $this->addScopeForPaymentMappingTable($installer);
        }

        $installer->endSetup();
    }

    /**
     * Create the table name magenest_xero_request
     *
     * @param SetupInterface $installer
     * @return void
     */
    private function createXeroRequestTable($installer)
    {
        $tableName = InstallSchema::MODULE_TABLE_PREFIX . 'xero_request';
        if ($installer->tableExists($tableName)) {
            return;
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
            'date',
            Table::TYPE_DATE,
            null,
            ['nullable' => false],
            'Date'
        )->addColumn(
            'request',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Request'
        )->setComment(
            'Xero Request Table'
        );

        $installer->getConnection()->createTable($table);
    }

    /**
     * @param SetupInterface $installer
     * @return void
     */
    private function createXeroPaymentAccountMapping($installer)
    {
        $tableName = InstallSchema::MODULE_TABLE_PREFIX . 'xero_payment_account_mapping';
        if ($installer->tableExists($tableName)) {
            return;
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
            'payment_code',
            Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Date'
        )->addColumn(
            'bank_account_name',
            Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Xero Bank Account Name'
        )->addColumn(
            'bank_account_id',
            Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Xero Bank Account ID'
        )->addColumn(
            'updated_at',
            Table::TYPE_DATE,
            null,
            ['nullable' => true],
            'updated at'
        )->setComment(
            'Xero Payment Account Mapping'
        );

        $installer->getConnection()->createTable($table);
    }

    private function createXeroTaxRatesMapping($installer)
    {
        $tableName = InstallSchema::MODULE_TABLE_PREFIX . 'xero_tax_rate_mapping';
        if ($installer->tableExists($tableName)) {
            return;
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
            'tax_id',
            Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Tax identifier'
        )->addColumn(
            'xero_tax_code',
            Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Xero Tax Code'
        )->addColumn(
            'updated_at',
            Table::TYPE_DATE,
            null,
            ['nullable' => true],
            'updated at'
        )->setComment(
            'Xero Tax Rate Mapping'
        );

        $installer->getConnection()->createTable($table);
    }

    private function createXeroXmlLogTable($installer)
    {
        {
            $tableName = InstallSchema::MODULE_TABLE_PREFIX . 'xero_xml_log';
//            if ($installer->tableExists($tableName)) {
//                return;
//            }

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
                'xml_log',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true],
                'Xml Log'
            )->addColumn(
                'magento_id',
                Table::TYPE_TEXT,
                15,
                ['nullable' => true],
                'Magento Entity Id'
            )->addColumn(
                'type',
                Table::TYPE_TEXT,
                15,
                ['nullable' => false],
                'Magento Type'
            )->setComment(
                'Xero Xml Log Table'
            );

            $installer->getConnection()->createTable($table);
        }
    }
    /**
     * @param SchemaSetupInterface $installer
     */
    private function addForeignKeyToLogTable($installer)
    {

        $installer->getConnection()->addColumn(
            $installer->getTable(InstallSchema::MODULE_TABLE_PREFIX . 'xero_log'),
            'xml_log_id',
            [
                'type' => Table::TYPE_INTEGER,
                'comment' => 'Xml Log Id',
                'unsigned' => true,
                'nullable' => true,
            ]
        );
        $installer->getConnection()->addForeignKey(
            $installer->getFkName(
                InstallSchema::MODULE_TABLE_PREFIX . 'xero_log',
                'xml_log_id',
                InstallSchema::MODULE_TABLE_PREFIX . 'xero_xml_log',
                'id'
            ),
            $installer->getTable(InstallSchema::MODULE_TABLE_PREFIX . 'xero_log'),
            'xml_log_id',
            $installer->getTable(InstallSchema::MODULE_TABLE_PREFIX . 'xero_xml_log'),
            'id',
            Table::ACTION_SET_NULL
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    private function addScopeForXeroXmlLogTable($installer)
    {
        $installer->getConnection()->addColumn(
            $installer->getTable(InstallSchema::MODULE_TABLE_PREFIX . 'xero_xml_log'),
            'scope',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 11,
                'comment' => 'Scope',
                'nullable' => false,
                'default' => 'default'
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable(InstallSchema::MODULE_TABLE_PREFIX . 'xero_xml_log'),
            'scope_id',
            [
                'type' => Table::TYPE_INTEGER,
                'size' => 3,
                'comment' => 'Scope ID',
                'nullable' => false,
                'default' => '0',
                'unsigned' => true
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    private function addScopeForTaxRateMappingTable($installer)
    {
        $installer->getConnection()->addColumn(
            $installer->getTable(InstallSchema::MODULE_TABLE_PREFIX . 'xero_tax_rate_mapping'),
            'scope',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 11,
                'comment' => 'Scope',
                'nullable' => false,
                'default' => 'default'
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable(InstallSchema::MODULE_TABLE_PREFIX . 'xero_tax_rate_mapping'),
            'scope_id',
            [
                'type' => Table::TYPE_INTEGER,
                'size' => 3,
                'comment' => 'Scope ID',
                'nullable' => false,
                'default' => '0',
                'unsigned' => true
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    private function addScopeForPaymentMappingTable($installer)
    {
        $installer->getConnection()->addColumn(
            $installer->getTable(InstallSchema::MODULE_TABLE_PREFIX . 'xero_payment_account_mapping'),
            'scope',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 11,
                'comment' => 'Scope',
                'nullable' => false,
                'default' => 'default'
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable(InstallSchema::MODULE_TABLE_PREFIX . 'xero_payment_account_mapping'),
            'scope_id',
            [
                'type' => Table::TYPE_INTEGER,
                'size' => 3,
                'comment' => 'Scope ID',
                'nullable' => false,
                'default' => '0',
                'unsigned' => true
            ]
        );
    }
}
