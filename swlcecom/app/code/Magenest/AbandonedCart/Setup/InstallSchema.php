<?php

namespace Magenest\AbandonedCart\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    const ABANDONED_CART_TABLE = 'magenest_abacar_list';

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $magenest_abacar_rule = $installer->getConnection()->newTable(
            $installer->getTable('magenest_abacar_rule')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null, [
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ],
            'Rule Id'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Rule Name'
        )->addColumn(
            'description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Rule Description'
        )->addColumn(
            'from_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null, [
            'nullable' => true,
            'default'  => null,
        ],
            'From Date'
        )->addColumn(
            'to_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            [
                'nullable' => true,
                'default'  => null,
            ],
            'To Date'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null, [
            'nullable' => false
        ],
            'Rule Status'
        )->addColumn(
            'stores_view',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Stores View Ids'
        )->addColumn(
            'customer_group',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Customer Group Ids'
        )->addColumn(
            'discard_subsequent',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            [],
            'Discard Subsequent'
        )->addColumn(
            'priority',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            [],
            'Priority'
        )->addColumn(
            'cancel_rule_when',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Cancel Rule When'
        )->addColumn(
            'conditions_serialized',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            [],
            'Conditions Serialized'
        )->addColumn(
            'cancel_serialized',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            [],
            'Conditions to cancel queue mail Serialized'
        )->addColumn(
            'email_chain',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            [],
            'Email Chain'
        )->addColumn(
            'sms_chain',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            [],
            'SMS Chain'
        )->addColumn(
            'duplicated_key',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Prevent duplidated follow up email'
        )->addColumn(
            'enable_coupon',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            [
                'nullable' => false,
                'default'  => 0,
            ],
            'Allow insert coupon in email or not'
        )->addColumn(
            'use_cart_rule',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            [
                'nullable' => false,
                'default'  => 0,
            ],
            'Use Cart Price Rule or Not'
        )->addColumn(
            'custom_coupon',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            [],
            'Custom general coupon'
        )->addColumn(
            'promotion_rule',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'Promotion Rule Id'
        )->addColumn(
            'ga_source',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Google analytics source'
        )->addColumn(
            'ga_medium',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Google analytics medium'
        )->addColumn(
            'ga_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Google analytics name'
        )->addColumn(
            'ga_term',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Google analytics term'
        )->addColumn(
            'ga_content',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Google analytics content'
        )->addColumn(
            'attached_files',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Attach files which is serialized'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null, [
            'nullable' => false,
            'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
        ],
            'Created At'
        )->addColumn(
            'additional_settings',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Additional Settings for individual Rule'
        )->setComment('Abandoned Cart Rule Table');
        $installer->getConnection()->createTable($magenest_abacar_rule);

        $magenest_abacar_testcampaign = $installer->getConnection()->newTable(
            $installer->getTable('magenest_abacar_testcampaign')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null, [
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ],
            'Test Campaign Id'
        )->addColumn(
            'quote_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Quote Id'
        )->addColumn(
            'coupon_generated',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Coupon Generated'
        )->addColumn(
            'customer_email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Cusotmer Email'
        )->addColumn(
            'cart_subtotal',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Cart SubTotal'
        )->addColumn(
            'status_cart',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Status Cart'
        )->addColumn(
            'rule_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Rule Id'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null, [
            'nullable' => false,
            'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT

        ],
            'Cart Created Time'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null, [
            'nullable' => false,
            'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT
        ],
            'Cart Update Time'
        )->addColumn(
            'sent_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [],
            'Sent Date Email'
        )->addColumn(
            'is_send',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Is Send'
        )->setComment('Abandoned Cart Test Campaign Table');
        $installer->getConnection()->createTable($magenest_abacar_testcampaign);

        $magenest_abacar_list = $installer->getConnection()->newTable(
            $installer->getTable('magenest_abacar_list')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null, [
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ],
            'Abandoned Cart Id'
        )->addColumn(
            'quote_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null, [
            'nullable' => false
        ],
            'Quote Id'
        )->addColumn(
            'customer_email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null, [
            'nullable' => false
        ],
            'Customer Email'
        )->addColumn(
            'customer_phone',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null, [
            'nullable' => false
        ],
            'Customer Phone'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [
                'nullable' => false,
                'default'  => 0,
            ],
            'Status Id'
        )->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null, [
            'nullable' => false
        ],
            'Customer Phone'
        )->addColumn(
            'is_processed',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null, [
            'nullable' => false,
            'default'  => 0
        ],
            'Is processed'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
            ],
            'Created At'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null, [
            'nullable' => false,
            'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_UPDATE,
        ],
            'Updated At'
        )->addColumn(
            'placed',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Placed Order'
        )->setComment('Abandoned Cart List Table');
        $installer->getConnection()->createTable($magenest_abacar_list);

        $magenest_abacar_unsubscribe = $installer->getConnection()->newTable(
            $installer->getTable('magenest_abacar_unsubscribe')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ]
        )->addColumn(
            'unsubscriber_email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null, [
                'nullable' => false,
                'unsigned' => true
            ]
        )->addColumn(
            'unsubscriber_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null, [
            'nullable' => false,
            'default'  => 0
        ],
            'Status Id'
        )->addColumn(
            'rule_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null, [
            'nullable' => false
        ],
            'Rule Id'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
            ],
            'Created At'
        )->setComment('Unsubscribe Table');
        $installer->getConnection()->createTable($magenest_abacar_unsubscribe);

        $magenest_abacar_log = $installer->getConnection()->newTable(
            $installer->getTable('magenest_abacar_log')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null, [
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true
        ],
            'Abandoned Cart Log'
        )->addColumn(
            'abandonedcart_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null, [
            'nullable' => false
        ],
            'Abandoned Cart Id'
        )->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null, [
            'nullable' => false
        ],
            'Email or SMS'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null, [
            'nullable' => false
        ],
            'Status'
        )->addColumn(
            'store_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [
                'nullable' => false,
                'unsigned' => true,
            ],
            'Store Id'
        )->addColumn(
            'rule_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            255,
            [
                'nullable' => false,
                'unsigned' => true,
            ],
            'The mail  is associated with a rule'
        )->addColumn(
            'duplicated_key',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Information to prevent duplicating email'
        )->addColumn(
            'recipient_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255, [
            'nullable' => false,
            'default'  => 0
        ],
            'Recipient name'
        )->addColumn(
            'recipient_adress',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255, [
            'nullable' => false,
            'default'  => 0
        ],
            'Recipient Address'
        )->addColumn(
            'bcc_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Bcc name'
        )->addColumn(
            'bcc_email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Bcc name'
        )->addColumn(
            'send_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null, [
            'nullable' => false,
            'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
        ],
            'Send Date'
        )->addColumn(
            'subject',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255, [
            'nullable' => false
        ],
            'Subject of email'
        )->addColumn(
            'styles',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Email Styles'
        )->addColumn(
            'content',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Content Send To Customer'
        )->addColumn(
            'attachments',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'File path of attachments'
        )->addColumn(
            'cancel_serialized',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            [],
            'Cancel email automatically base on this trigger'
        )->addColumn(
            'log',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Mail Status Log'
        )->addColumn(
            'clicks',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null, [
            'nullable' => false,
            'default'  => 0,
        ],
            'Recipient Clicks Mail'
        )->addColumn(
            'opened',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null, [
            'nullable' => false,
            'default'  => 0,
        ],
            'Recipient Opened Mail'
        )->addColumn(
            'preview_content',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Preview Content'
        )->addColumn(
            'context_vars',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Context Variables'
        )->addColumn(
            'coupon_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Coupon Code'
        )->addColumn(
            'template_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'Email Template Id'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null, [
            'nullable' => false,
            'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
        ],
            'Created At'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
            ],
            'Updated At'
        )->addColumn(
            'is_restore',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            255,
            ['nullable' => false, 'default' => 0],
            'Is Restore'
        )->addColumn(
            'quote_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => false],
            'Quote Id'
        )->setComment('Abandoned Cart Log');
        $installer->getConnection()->createTable($magenest_abacar_log);

        $magenest_abacar_blacklist = $installer->getConnection()->newTable(
            $installer->getTable('magenest_abacar_blacklist')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null, [
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ],
            'Black List Id'
        )->addColumn(
            'address',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null, [
            'nullable' => false
        ],
            'Addess'
        )->setComment('Black List Table');
        $installer->getConnection()->createTable($magenest_abacar_blacklist);

        $magenest_abacar_cronlog = $installer->getConnection()->newTable(
            $installer->getTable('magenest_abacar_cronlog')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null, [
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ],
            'Cron Log Id'
        )->addColumn(
            'message',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Message'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Status'
        )->addColumn(
            'magento_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            15,
            ['nullable' => true],
            'Magento Entity Id'
        )->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => false],
            'Magento Type'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null, [
            'nullable' => false,
            'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
        ],
            'Created At'
        )->addColumn(
            'executed_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null, [
            'nullable' => false,
            'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
        ],
            'Executed At'
        )->addColumn(
            'finished_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null, [
            'nullable' => false,
            'default'  => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
        ],
            'Finished At'
        )->setComment('Cron Log Table');
        $installer->getConnection()->createTable($magenest_abacar_cronlog);

        $magenest_abacar_guest_capture = $setup->getConnection()
            ->newTable($setup->getTable('magenest_abacar_guest_capture'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Field Mapping id'
            )
            ->addColumn(
                'quote_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'Quote id'
            )
            ->addColumn(
                'email',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Billing email guest enters in checkout'
            )
            ->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Type is guest or customer'
            )
            ->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => 0],
                'Status Id'
            )
            ->setComment('Guest email');
        $installer->getConnection()->createTable($magenest_abacar_guest_capture);

        $installer->endSetup();
    }
}