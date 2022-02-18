<?php
namespace Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit\Tab;

class Category extends \Magento\Backend\Block\Widget\Form\Generic
{
    protected $_categorytree;
    protected $categoryFlatConfig;
    protected $_category;
   
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magebees\Promotionsnotification\Model\Category $category,
        \Magento\Catalog\Model\ResourceModel\Category\Tree $categorytree,
        \Magento\Catalog\Model\Category $categoryFlatState,

                array $data = []
    ) {
        $this->_categorytree = $categorytree;
        $this->categoryFlatConfig = $categoryFlatState;
        $this->_category = $category;
                
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function buildCategoriesMultiselectValues($node, $values, $level = 0)
    {
        $nonEscapableNbspChar = html_entity_decode('&#160;', ENT_NOQUOTES, 'UTF-8');
        $level++;
        if ($level > 1) {
            $values[$node->getId()]['value'] = $node->getId();
            $values[$node->getId()]['label'] = str_repeat($nonEscapableNbspChar, ($level - 2) * 5).$node->getName();
        }

        foreach ($node->getChildren() as $child) {
            $values = $this->buildCategoriesMultiselectValues($child, $values, $level);
        }

        return $values;
    }

    public function toOptionArray()
    {
        $tree = $this->_categorytree->load();
        $parentId = 1;
        $root = $tree->getNodeById($parentId);

        if ($root && $root->getId() == 1) {
            $root->setName('Root');
        }

        $collection = $this->categoryFlatConfig->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active');

        $tree->addCollectionData($collection, true);

        $values['---'] = [
            'value' => 0,
            'label' => 'Display on All Categories Pages',
        ];

        return $this->buildCategoriesMultiselectValues($root, $values);
    }
    
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('notification_data');
        $isElementDisabled = false;
        $form = $this->_formFactory->create();
        
       // $form->setHtmlIdPrefix('categories_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Categories')]);

        $categories = $this->_category->getCollection()
            ->addFieldToFilter('notification_id', ['eq' => $model->getId()]);
        $categories_val = [];
        foreach ($categories as $categories_data) {
            $categories_val[] = $categories_data->getData('category_ids');
        }
        
        $group_name = $fieldset->addField(
            'category_ids',
            'multiselect',
            [
                'name' => 'category_ids[]',
                'label' => __('Visible In'),
                'title' => __('Visible In'),
                'required' => false,
                'values' => $this->toOptionArray(),
                'disabled' => $isElementDisabled,
                'value'         => $categories_val,
            ]
        );
                        
        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
