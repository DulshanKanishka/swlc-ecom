<?php
namespace Magenest\Xero\Setup;

use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;

/**
 * Upgrade Data script
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * UpgradeData constructor.
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '3.1.4') < 0) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $this->setXeroAccountProperty($eavSetup);
        }
        $setup->endSetup();
    }

    public function setXeroAccountProperty($eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'sale_id',
            [
                'group' => 'General',
                'type' => 'text',
                'visible' => 1,
                'required' => 0,
                'input' => 'select',
                'user_defined' => 1,
                'filterable' => 1,
                'visible_on_front' => 0,
                'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_WEBSITE,
                'label' => 'Xero Sales Account',
                'system' => 0,
                'is_used_in_grid' => 1,
                'is_visible_in_grid' => 1,
                'is_filterable_in_grid' => 1,
                'source' => 'Magenest\Xero\Model\Config\Account\SalesAccount'
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'cogs_id',
            [
                'group' => 'General',
                'type' => 'text',
                'visible' => 1,
                'required' => 0,
                'input' => 'select',
                'user_defined' => 1,
                'filterable' => 1,
                'visible_on_front' => 0,
                'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_WEBSITE,
                'label' => 'Xero COGS Account',
                'system' => 0,
                'is_used_in_grid' => 1,
                'is_visible_in_grid' => 1,
                'is_filterable_in_grid' => 1,
                'source' => 'Magenest\Xero\Model\Config\Account\COGSAccount'
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'tracking_category',
            [
                'group' => 'General',
                'type' => 'text',
                'visible' => 1,
                'required' => 0,
                'input' => 'select',
                'user_defined' => 1,
                'filterable' => 1,
                'visible_on_front' => 0,
                'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_WEBSITE,
                'label' => 'Xero Tracking Category',
                'system' => 0,
                'is_used_in_grid' => 1,
                'is_visible_in_grid' => 1,
                'is_filterable_in_grid' => 1,
                'source' => 'Magenest\Xero\Model\Config\TrackingCategory\TrackingCategory'
            ]
        );
    }
}
