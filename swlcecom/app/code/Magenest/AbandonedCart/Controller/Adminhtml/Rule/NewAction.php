<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Rule;

class NewAction extends \Magento\Backend\App\Action
{

    public function execute()
    {
        $this->_forward('edit');
    }
}