<?php
namespace Meetanshi\PaymentRestriction\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class Rule extends Container
{
    protected function _construct()
    {
        $this->_controller = 'rule';
        $this->_headerText = __('Payment Restriction Rules');
        $this->_addButtonLabel = __('Add Rule');
        parent::_construct();
    }
}
