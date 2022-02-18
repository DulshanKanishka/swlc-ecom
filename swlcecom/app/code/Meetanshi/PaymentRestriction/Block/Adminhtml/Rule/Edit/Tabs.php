<?php

namespace Meetanshi\PaymentRestriction\Block\Adminhtml\Rule\Edit;

use Magento\Backend\Block\Widget\Tabs as PayTabs;

class Tabs extends PayTabs
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('paymentrestriction_rule_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Payment Restriction Rules Options'));
    }
}
