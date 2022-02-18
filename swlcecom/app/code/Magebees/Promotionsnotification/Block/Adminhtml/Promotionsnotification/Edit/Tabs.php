<?php
namespace Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('notification_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Notification Information'));
    }
    
    protected function _prepareLayout()
    {
        
        $this->addTab(
            'general_section',
            [
                'label' => __('General'),
                'title' => __('General'),
                'content' => $this->getLayout()->createBlock(
                    'Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit\Tab\General'
                )->toHtml(),
                'active' => true
            ]
        );
        
        $this->addTab(
            'page_section',
            [
                'label' => __('Display on Pages'),
                'title' => __('Display on Pages'),
                'content' => $this->getLayout()->createBlock(
                    'Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit\Tab\Page'
                )->toHtml()
            ]
        );
        
        $this->addTab(
            'category_section',
            [
                'label' => __('Display on Categories'),
                'title' => __('Display on Categories'),
                'content' => $this->getLayout()->createBlock(
                    'Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit\Tab\Category'
                )->toHtml()
            ]
        );
    
        
        
        /* $this->addTab(
			'product_section',
			[
				'label' => __('Display on Products'),
				'title' => __('Display on Products'),
				'content' => $this->getLayout()->createBlock(
					'Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit\Tab\Product'
				)->toHtml()
			]
		);
		 */
        
        $this->addTab(
            'product_section',
            [
                'label' => __('Display on Products'),
                'title' => __('Display on Products'),
                'url' => $this->getUrl('promotions/notification/producttab', ['_current' => true]),
                'class' => 'ajax'
            ]
        );
        
        if ($this->getRequest()->getParam('id')) {
            $this->addTab(
                'code_section',
                [
                    'label' => __('Use Code Inserts'),
                    'title' => __('Use Code Inserts'),
                    'content' => $this->getLayout()->createBlock(
                        'Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit\Tab\Code'
                    )->toHtml()
                ]
            );
        }
                
        return parent::_prepareLayout();
    }
}
