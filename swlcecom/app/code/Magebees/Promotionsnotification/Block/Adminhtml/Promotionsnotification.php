<?php
namespace Magebees\Promotionsnotification\Block\Adminhtml;

class Promotionsnotification extends \Magento\Backend\Block\Widget\Grid\Container
{
    protected function _construct()
    {
        
        $this->_controller = 'adminhtml_promotionsnotification';
        $this->_blockGroup = 'Magebees_Promotionsnotification';
        $this->_headerText = __('Manage Notifications');
        $this->_addButtonLabel = __('Add New Notification');
        parent::_construct();
    }
}
