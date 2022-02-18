<?php

namespace Magenest\AbandonedCart\Setup;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{

    protected $customerSetupFactory;

    protected $attributeSetFactory;

    /**
     * InstallData constructor.
     *
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory  = $attributeSetFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
        $customerSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'mobile_number',
            [
                'type'     => 'varchar',
                'backend'  => '',
                'label'    => 'Mobile',
                'input'    => 'text',
                'source'   => '',
                'visible'  => true,
                'required' => false,
                'default'  => '',
                'frontend' => '',
                'unique'   => false,
                'note'     => '',
            ]
        );

        $my_attribute    = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'mobile_number');
        $used_in_forms[] = 'adminhtml_customer';
        $used_in_forms[] = 'checkout_register';
        $used_in_forms[] = 'customer_account_create';
        $used_in_forms[] = 'customer_account_edit';
        $used_in_forms[] = 'adminhtml_checkout';
        $my_attribute->setData('used_in_forms', $used_in_forms)
            ->setData('is_used_for_customer_segment', true)
            ->setData('is_system', 0)
            ->setData('is_user_defined', 1)
            ->setData('is_visible', 1)
            ->setData('sort_order', 100);
        $my_attribute->save();

        $installer->endSetup();
    }
}