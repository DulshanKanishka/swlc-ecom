<?php

namespace Meetanshi\PaymentRestriction\Block\Adminhtml\Rule\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Input;
use Magento\Framework\DataObject;
use Meetanshi\PaymentRestriction\Helper\Data;

class Groups extends Input
{
    protected $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function render(DataObject $row)
    {
        $groups = $row->getData('cust_groups');
        if (!$groups) {
            return __('Restricts For All');
        }
        $groups = explode(',', $groups);

        $html = '';
        foreach ($this->helper->getAllCustomerGroups() as $row) {
            if (in_array($row['value'], $groups)) {
                $html .= $row['label'] . "<br />";
            }
        }
        return $html;
    }
}
