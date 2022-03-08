<?php

namespace Dulshan\Custom\Model\FlexField\Extractor;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Vertex\Tax\Model\FlexField\Extractor\EavTypeExtractor;
use Vertex\Tax\Model\FlexField\FlexFieldProcessableAttributeFactory;

class EavAttributeExtractor extends \Vertex\Tax\Model\FlexField\Extractor\EavAttributeExtractor
{
    private $attributeFactory;
    private $typeExtractor;
    private $attributeRepository;
    private $searchCriteriaBuilder;

    public function __construct(
        FlexFieldProcessableAttributeFactory $attributeFactory,
        EavTypeExtractor $typeExtractor,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->typeExtractor = $typeExtractor;
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($attributeFactory, $typeExtractor, $attributeRepository, $searchCriteriaBuilder);
    }

    private function getCustomAttributeList($eavEntityCode, array $blacklist = [])
    {
        $searchBuilder = $this->searchCriteriaBuilder;

        if (!empty($blacklist)) {
            $searchBuilder = $searchBuilder->addFilter('attribute_code', $blacklist, 'nin');
        }

        $searchCriteria = $searchBuilder
            ->addFilter('backend_type', 'static', 'neq')
            ->create();

        return $this->attributeRepository->getList($eavEntityCode, $searchCriteria)->getItems();
    }

    public function extract($eavEntityCode, $prefix, $optionGroup, $processor, array $blacklist = [])
    {
        $prefix .= '.' . self::CUSTOM_PREFIX;
        $customAttributes = $this->getCustomAttributeList($eavEntityCode, $blacklist);
        $attributes = [];

        foreach ($customAttributes as $eavAttribute) {
            $type = $this->typeExtractor->extract($eavAttribute);
            $attribute = $this->attributeFactory->create();
            $attributeCode = $prefix . '.' . $eavAttribute->getAttributeCode();
            $attribute->setAttributeCode($attributeCode);
//            $attribute->setLabel($eavAttribute->getDefaultFrontendLabel());
            $attribute->setLabel($eavAttribute->getDefaultFrontendLabel() ?: $eavAttribute->getAttributeCode());
            $attribute->setOptionGroup(__($optionGroup)->render());
            $attribute->setType($type);
            $attribute->setProcessor($processor);
            $attributes[$attributeCode] = $attribute;
        }
        return $attributes;
    }
}
