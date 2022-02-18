<?php

namespace NeoSolax\DataImport\Console\Command;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Attribute;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ResourceConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportAttribute extends Command
{
    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    /**
     * @var Config
     */
    private $eavConfig;
    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;
    /**
     * @var Attribute
     */
    private $eavAttribute;
    /**
     * @var Entity
     */
    private $entity;

    public function __construct(
        ResourceConnection $resource,
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        AttributeSetFactory $attributeSetFactory,
        Attribute $eavAttribute,
        Entity $entity,
        $name = null
    ) {
        parent::__construct($name);
        $this->resource = $resource;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->eavAttribute = $eavAttribute;
        $this->entity = $entity;
    }

    protected function configure()
    {
        $this->setName('migrate:attributes');
        $this->setDescription('Migrate Attributes from old Database to New Database');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connectionOld = $this->resource->getConnection('old_setup');
        $tableName =  $this->resource->getTableName('eav_attribute');
        $sql = "Select * FROM " . $tableName . " where entity_type_id=4";
        $result = $connectionOld->fetchAll($sql);

        $attributeCollection = $this->eavAttribute->getCollection();
        $attributeCollection->addFieldToFilter('is_user_defined', 1);
        $attributeCollection->addFieldToFilter('is_user_defined', 0);
        $attributeCollection->addFieldToFilter('entity_type_id', $this->entity->setType('catalog_product')->getTypeId());
        $currentAttributes = $attributeCollection->getItems();
        $currentAttributesCodes = [];
        foreach ($currentAttributes as $currentAttribute) {
            $currentAttributesCodes[] = $currentAttribute->getAttributeCode();
        }

        foreach ($result as $attribute) {
            if (!in_array($attribute['attribute_code'], $currentAttributesCodes)) {
                if ($attribute['attribute_code'] !== 'gift_card_type'
                    && $attribute['attribute_code'] !== 'gift_price_type'
                    && $attribute['attribute_code'] !== 'gift_type'
                    && $attribute['attribute_code'] !== 'gift_code_sets'
                    && $attribute['attribute_code'] !== 'gift_template_ids'
                    && $attribute['attribute_code'] !== 'product_brand'
                    && $attribute['attribute_code'] !== 'storecredit_type'
                    && $attribute['attribute_code'] !== 'samples_title'
                    && $attribute['attribute_code'] !== 'links_title'
                    && $attribute['attribute_code'] !== 'brand'
                    && $attribute['attribute_code'] !== 'brandsku') {
                    $this->addAttribute($attribute);
                    echo "Attribute created : " . $attribute['attribute_code'] . "\n";
                }
            }
        }
    }

    protected function addAttribute($attributeData)
    {
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttribute(
            Product::ENTITY,
            $attributeData['attribute_code'],
            [
                'group' => 'General',
                'type' => $attributeData['backend_type'],
                'label' => $attributeData['frontend_label'],
                'backend' => $attributeData['backend_model'],
                'frontend' => $attributeData['frontend_model'],
                'input' =>  $attributeData['frontend_input'],
                'class' =>  $attributeData['frontend_class'],
                'source' =>  $attributeData['source_model'],
                'global' =>  \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required' =>  $attributeData['is_required'],
                'user_defined' =>  $attributeData['is_user_defined'],
                'default' => $attributeData['default_value'],
                'unique' =>  $attributeData['is_unique'],
                ]
        );
    }
}
