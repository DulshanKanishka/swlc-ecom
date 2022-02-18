<?php

namespace Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;

use Magento\Framework\App\ResponseInterface;
use Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;

class NewAction extends Rule
{
    public function execute()
    {
        $this->_forward('edit');
    }
}
