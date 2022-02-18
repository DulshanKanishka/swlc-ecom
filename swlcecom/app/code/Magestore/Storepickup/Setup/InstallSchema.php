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
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magestore\Storepickup\Model\Schedule\Option\WeekdayStatus;

/**
 * @category Magestore
 * @package  Magestore_Storepickup
 * @module   Storepickup
 * @author   Magestore Developer
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Schema table.
     */
    const SCHEMA_STORE = 'magestore_storepickup_store';
    const SCHEMA_IMAGE = 'magestore_storepickup_image';
    const SCHEMA_SCHEDULE = 'magestore_storepickup_schedule';
    const SCHEMA_TAG = 'magestore_storepickup_tag';
    const SCHEMA_SPECIALDAY = 'magestore_storepickup_specialday';
    const SCHEMA_HOLIDAY = 'magestore_storepickup_holiday';
    const SCHEMA_STORE_TAG = 'magestore_storepickup_store_tag';
    const SCHEMA_STORE_HOLIDAY = 'magestore_storepickup_store_holiday';
    const SCHEMA_STORE_SPECIALDAY = 'magestore_storepickup_store_specialday';
    const SCHEMA_ORDER = 'magestore_storepickup_order';
    const SCHEMA_OWNER = 'magestore_storepickup_owner';

    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $installer->getConnection()->dropTable($installer->getTable(self::SCHEMA_IMAGE));
        $installer->getConnection()->dropTable($installer->getTable(self::SCHEMA_STORE_TAG));
        $installer->getConnection()->dropTable($installer->getTable(self::SCHEMA_STORE_HOLIDAY));
        $installer->getConnection()->dropTable($installer->getTable(self::SCHEMA_STORE_SPECIALDAY));
        $installer->getConnection()->dropTable($installer->getTable(self::SCHEMA_STORE));
        $installer->getConnection()->dropTable($installer->getTable(self::SCHEMA_TAG));
        $installer->getConnection()->dropTable($installer->getTable(self::SCHEMA_SCHEDULE));
        $installer->getConnection()->dropTable($installer->getTable(self::SCHEMA_HOLIDAY));
        $installer->getConnection()->dropTable($installer->getTable(self::SCHEMA_SPECIALDAY));
        $installer->getConnection()->dropTable($installer->getTable(self::SCHEMA_ORDER));

        /*
         * Create table magestore_storepickup_schedule
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::SCHEMA_SCHEDULE)
        )->addColumn(
            'schedule_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Schedule Id'
        )->addColumn(
            'schedule_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => ''],
            'Schedule Name'
        )->addColumn(
            'time_interval',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => ''],
            'Schedule Name'
        )->addColumn(
            'monday_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'unsigned' => true, 'default' => WeekdayStatus::WEEKDAY_STATUS_OPEN],
            'Monday Status'
        )->addColumn(
            'tuesday_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'unsigned' => true, 'default' => WeekdayStatus::WEEKDAY_STATUS_OPEN],
            'Tuesday Status'
        )->addColumn(
            'wednesday_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'unsigned' => true, 'default' => WeekdayStatus::WEEKDAY_STATUS_OPEN],
            'Wednesday Status'
        )->addColumn(
            'thursday_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'unsigned' => true, 'default' => WeekdayStatus::WEEKDAY_STATUS_OPEN],
            'Thursday Status'
        )->addColumn(
            'friday_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'unsigned' => true, 'default' => WeekdayStatus::WEEKDAY_STATUS_OPEN],
            'Friday Status'
        )->addColumn(
            'saturday_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'unsigned' => true, 'default' => WeekdayStatus::WEEKDAY_STATUS_OPEN],
            'Saturday Status'
        )->addColumn(
            'sunday_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'unsigned' => true, 'default' => WeekdayStatus::WEEKDAY_STATUS_OPEN],
            'Sunday Status'
        )->addColumn(
            'monday_open',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Monday Open'
        )->addColumn(
            'tuesday_open',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Tuesday Open'
        )->addColumn(
            'wednesday_open',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Wednesday Open'
        )->addColumn(
            'thursday_open',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Thursday Open'
        )->addColumn(
            'friday_open',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Friday Open'
        )->addColumn(
            'saturday_open',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Saturday Open'
        )->addColumn(
            'sunday_open',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Sunday Open'
        )->addColumn(
            'monday_open_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Monday Open Break'
        )->addColumn(
            'tuesday_open_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Tuesday Open Break'
        )->addColumn(
            'wednesday_open_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Wednesday Open Break'
        )->addColumn(
            'thursday_open_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Thursday Open Break'
        )->addColumn(
            'friday_open_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Friday Open Break'
        )->addColumn(
            'saturday_open_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Saturday Open Break'
        )->addColumn(
            'sunday_open_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Sunday Open Break'
        )->addColumn(
            'monday_close_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Monday Close Break'
        )->addColumn(
            'tuesday_close_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Tuesday Close Break'
        )->addColumn(
            'wednesday_close_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Wednesday Close Break'
        )->addColumn(
            'thursday_close_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Thursday Close Break'
        )->addColumn(
            'friday_close_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Friday Close Break'
        )->addColumn(
            'saturday_close_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Saturday Close Break'
        )->addColumn(
            'sunday_close_break',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Sunday Close Break'
        )->addColumn(
            'monday_close',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Monday Close'
        )->addColumn(
            'tuesday_close',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Tuesday Close'
        )->addColumn(
            'wednesday_close',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Wednesday Close'
        )->addColumn(
            'thursday_close',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Thursday Close'
        )->addColumn(
            'friday_close',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Friday Close'
        )->addColumn(
            'saturday_close',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Saturday Close'
        )->addColumn(
            'sunday_close',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Sunday Close'
        )->addIndex(
            $setup->getIdxName(
                $installer->getTable(self::SCHEMA_SCHEDULE),
                ['schedule_name'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['schedule_name'],
            ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
        )->setComment(
            'Schedule Table'
        );

        $installer->getConnection()->createTable($table);
        /*
         * End create table magestore_storepickup_schedule
         */
        /*
         * Create table magestore_storepickup_store
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::SCHEMA_STORE)
        )->addColumn(
            'storepickup_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Storepickup Id'
        )->addColumn(
            'address',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => ''],
            'Adress'
        )->addColumn(
            'baseimage_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Base Image Id'
        )->addColumn(
            'city',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => ''],
            'City'
        )->addColumn(
            'country_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            2,
            ['nullable' => false, 'default' => 'US'],
            'Country Id'
        )->addColumn(
            'description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => false, 'default' => ''],
            'Description'
        )->addColumn(
            'email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => ''],
            'Email'
        )->addColumn(
            'fax',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            25,
            ['nullable' => false, 'default' => ''],
            'Fax'
        )->addColumn(
            'link',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            25,
            ['nullable' => true],
            'Store Link'
        )->addColumn(
            'latitude',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,8',
            ['nullable' => false, 'default' => '0.00000000'],
            'Latitude'
        )->addColumn(
            'longitude',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,8',
            ['nullable' => false, 'default' => '0.00000000'],
            'Longitude'
        )->addColumn(
            'marker_icon',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => false, 'default' => ''],
            'Marker Icon'
        )->addColumn(
            'meta_description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Meta Description'
        )->addColumn(
            'meta_keywords',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Meta Keywords'
        )->addColumn(
            'meta_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Meta Title'
        )->addColumn(
            'phone',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            25,
            ['nullable' => false, 'default' => ''],
            'Phone'
        )->addColumn(
            'rewrite_request_path',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => ''],
            'Rewrite Request Path'
        )->addColumn(
            'schedule_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Schedule Id'
        )->addColumn(
            'sort_order',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true, 'unsigned' => true],
            'Sort Order'
        )->addColumn(
            'state',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'State'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'unsigned' => true, 'default' => 1],
            'Status'
        )->addColumn(
            'store_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => ''],
            'Store Name'
        )->addColumn(
            'zipcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            25,
            ['nullable' => false, 'default' => ''],
            'Zip Code'
        )->addColumn(
            'zoom_level',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => '4'],
            'Zoom Level of Store in Google Map'
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_STORE),
                ['baseimage_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['baseimage_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_STORE),
                ['rewrite_request_path'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['rewrite_request_path'],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_STORE),
                ['schedule_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['schedule_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_STORE),
                ['store_name'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['store_name'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_STORE),
                ['country_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['country_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_STORE),
                ['state'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['state'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_STORE),
                ['zipcode'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['zipcode'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_STORE),
                ['address', 'city', 'state', 'zipcode'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['address', 'city', 'state', 'zipcode'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addIndex(
            $setup->getIdxName(
                $installer->getTable(self::SCHEMA_STORE),
                ['store_name', 'address', 'city', 'state', 'zipcode', 'description'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['store_name', 'address', 'city', 'state', 'zipcode', 'description'],
            ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
        )->addIndex(
            $setup->getIdxName(
                $installer->getTable(self::SCHEMA_STORE),
                ['phone', 'email', 'fax', 'meta_title', 'meta_keywords', 'meta_description'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['phone', 'email', 'fax', 'meta_title', 'meta_keywords', 'meta_description'],
            ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
        )->addForeignKey(
            $installer->getFkName(
                self::SCHEMA_STORE,
                'baseimage_id',
                self::SCHEMA_IMAGE,
                'image_id'
            ),
            'baseimage_id',
            $installer->getTable(self::SCHEMA_IMAGE),
            'image_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->addForeignKey(
            $installer->getFkName(
                self::SCHEMA_STORE,
                'schedule_id',
                self::SCHEMA_SCHEDULE,
                'schedule_id'
            ),
            'schedule_id',
            $installer->getTable(self::SCHEMA_SCHEDULE),
            'schedule_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        )->setComment(
            'Store Table'
        );

        $installer->getConnection()->createTable($table);
        /*
         * End create table magestore_storepickup_store
         */

        /*
         * Create table magestore_storepickup_tag
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::SCHEMA_TAG)
        )->addColumn(
            'tag_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Tag Id'
        )->addColumn(
            'tag_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => ''],
            'Tag Name'
        )->addColumn(
            'tag_description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Tag Description'
        )->addColumn(
            'tag_icon',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Tag Icon'
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_TAG),
                ['tag_name'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['tag_name'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addIndex(
            $setup->getIdxName(
                $installer->getTable(self::SCHEMA_TAG),
                ['tag_name', 'tag_description'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['tag_name', 'tag_description'],
            ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
        )->setComment(
            'Tag Table'
        );

        $installer->getConnection()->createTable($table);
        /*
         * End create table magestore_storepickup_tag
         */

        /*
         * Create table magestore_storepickup_store_tag
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::SCHEMA_STORE_TAG)
        )->addColumn(
            'storepickup_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Storepickup Id'
        )->addColumn(
            'tag_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Tag ID'
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_STORE_TAG),
                ['tag_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['tag_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addForeignKey(
            $installer->getFkName(
                self::SCHEMA_STORE_TAG,
                'tag_id',
                self::SCHEMA_TAG,
                'tag_id'
            ),
            'tag_id',
            $installer->getTable(self::SCHEMA_TAG),
            'tag_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->addForeignKey(
            $installer->getFkName(
                self::SCHEMA_STORE_TAG,
                'storepickup_id',
                self::SCHEMA_STORE,
                'storepickup_id'
            ),
            'storepickup_id',
            $installer->getTable(self::SCHEMA_STORE),
            'storepickup_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Store Tag Table'
        );

        $installer->getConnection()->createTable($table);
        /*
         * End create table magestore_storepickup_store_tag
         */

        /*
         * Create table magestore_storepickup_holiday
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::SCHEMA_HOLIDAY)
        )->addColumn(
            'holiday_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Holiday Id'
        )->addColumn(
            'holiday_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => ''],
            'Holiday Name'
        )->addColumn(
            'date_from',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            ['nullable' => true],
            'Date From'
        )->addColumn(
            'date_to',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            ['nullable' => true],
            'Date To'
        )->addColumn(
            'holiday_comment',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Holiday Comment'
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_HOLIDAY),
                ['holiday_name'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['holiday_name'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addIndex(
            $setup->getIdxName(
                $installer->getTable(self::SCHEMA_HOLIDAY),
                ['holiday_name', 'holiday_comment'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['holiday_name', 'holiday_comment'],
            ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
        )->setComment(
            'Holiday Table'
        );

        $installer->getConnection()->createTable($table);
        /*
         * End create table magestore_storepickup_holiday
         */

        /*
         * Create table magestore_storepickup_store_holiday
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::SCHEMA_STORE_HOLIDAY)
        )->addColumn(
            'storepickup_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Storepickup Id'
        )->addColumn(
            'holiday_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Holiday ID'
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_STORE_HOLIDAY),
                ['holiday_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['holiday_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addForeignKey(
            $installer->getFkName(
                self::SCHEMA_STORE_HOLIDAY,
                'holiday_id',
                self::SCHEMA_HOLIDAY,
                'holiday_id'
            ),
            'holiday_id',
            $installer->getTable(self::SCHEMA_HOLIDAY),
            'holiday_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->addForeignKey(
            $installer->getFkName(
                self::SCHEMA_STORE_HOLIDAY,
                'storepickup_id',
                self::SCHEMA_STORE,
                'storepickup_id'
            ),
            'storepickup_id',
            $installer->getTable(self::SCHEMA_STORE),
            'storepickup_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Store Holiday Table'
        );

        $installer->getConnection()->createTable($table);
        /*
         * End create table magestore_storepickup_store_holiday
         */

        /*
         * Create table magestore_storepickup_specialday
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::SCHEMA_SPECIALDAY)
        )->addColumn(
            'specialday_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Special day Id'
        )->addColumn(
            'specialday_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => ''],
            'Special day Name'
        )->addColumn(
            'specialday_comment',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true],
            'Special day Comment'
        )->addColumn(
            'date_from',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            ['nullable' => true],
            'Date From'
        )->addColumn(
            'date_to',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            ['nullable' => true],
            'Date To'
        )->addColumn(
            'time_open',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Time Open'
        )->addColumn(
            'time_close',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5,
            ['nullable' => false, 'default' => '00:00'],
            'Time Close'
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_SPECIALDAY),
                ['specialday_name'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['specialday_name'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addIndex(
            $setup->getIdxName(
                $installer->getTable(self::SCHEMA_SPECIALDAY),
                ['specialday_name', 'specialday_comment'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['specialday_name', 'specialday_comment'],
            ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
        )->setComment(
            'Special day Table'
        );

        $installer->getConnection()->createTable($table);
        /*
         * End create table magestore_storepickup_specialday
         */

        /*
         * Create table magestore_storepickup_store_specialday
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::SCHEMA_STORE_SPECIALDAY)
        )->addColumn(
            'storepickup_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Storepickup Id'
        )->addColumn(
            'specialday_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Holiday ID'
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_STORE_SPECIALDAY),
                ['specialday_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['specialday_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addForeignKey(
            $installer->getFkName(
                self::SCHEMA_STORE_SPECIALDAY,
                'specialday_id',
                self::SCHEMA_SPECIALDAY,
                'specialday_id'
            ),
            'specialday_id',
            $installer->getTable(self::SCHEMA_SPECIALDAY),
            'specialday_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->addForeignKey(
            $installer->getFkName(
                self::SCHEMA_STORE_SPECIALDAY,
                'storepickup_id',
                self::SCHEMA_STORE,
                'storepickup_id'
            ),
            'storepickup_id',
            $installer->getTable(self::SCHEMA_STORE),
            'storepickup_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Store Special day Table'
        );

        $installer->getConnection()->createTable($table);
        /*
         * End create table magestore_storepickup_store_specialday
         */

        /*
         * Create table magestore_storepickup_image
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::SCHEMA_IMAGE)
        )->addColumn(
            'image_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Image Id'
        )->addColumn(
            'path',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            1000,
            ['nullable' => false],
            'Relative Path Image'
        )->addColumn(
            'storepickup_id', //fix
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Storepickup Id'
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_IMAGE),
                ['path'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            [['name' => 'path', 'size' => 255]],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        )->addIndex(
            $installer->getIdxName(
                $installer->getTable(self::SCHEMA_IMAGE),
                ['storepickup_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['storepickup_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        )->addForeignKey(
            $installer->getFkName(
                self::SCHEMA_IMAGE,
                'storepickup_id',
                self::SCHEMA_STORE,
                'storepickup_id'
            ),
            'storepickup_id',
            $installer->getTable(self::SCHEMA_STORE),
            'storepickup_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Image Table'
        );

        $installer->getConnection()->createTable($table);
        /*
         * End create table magestore_storepickup_image
         */

        $installer->endSetup();
    }
}
