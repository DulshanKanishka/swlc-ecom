<?php
namespace Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit\Tab;

class Page extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
    protected $_cmsPage;
    protected $_pageModel;
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magebees\Promotionsnotification\Model\Page $pageModel,
        \Magento\Cms\Model\Page $cmsPage,

                array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_pageModel = $pageModel;
        $this->_cmsPage = $cmsPage;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    
    protected function getPagesValues()
    {
        $cmsCollection = $this->_cmsPage->getCollection();
        $options[] = array('value' => 0 , 'label' => 'Display on All CMS Pages');
        foreach ($cmsCollection as $cms) {
            $data = [
                'value' => $cms->getData('page_id'),
                'label' => $cms->getTitle()];
            $options[] = $data;
        }
        
        return $options;
    }
    
    
    protected function _prepareForm()
    {
        
        $model = $this->_coreRegistry->registry('notification_data');
        $isElementDisabled = false;
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('page_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Notification Pages')]);

        $page_model = $this->_pageModel->getCollection()
            ->addFieldToFilter('notification_id', ['eq' => $model->getId()]);
        $page = [];
        
        foreach ($page_model as $page_data) {
            $page[] = $page_data->getData('pages');
        }
              

        $fieldset->addField(
            'pages',
            'multiselect',
            [
                'name' => 'pages[]',
                'label' => __('Visible In'),
                'title' => __('Visible In'),
                'required' => false,
                'values' => $this->getPagesValues(),
                'disabled' => $isElementDisabled,
                'value'         => $page,
            ]
        );
        
        $fieldset->addField(
            'cart_page',
            'select',
            [
                'name' => 'cart_page',
                'label' => __('Visible In Cart Page'),
                'title' => __('Visible In Cart Page'),
                'required' => false,
                'values'    => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value'         => $model->getCartPage(),
            ]
        );
        
        $this->setForm($form);
         
        return parent::_prepareForm();
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
