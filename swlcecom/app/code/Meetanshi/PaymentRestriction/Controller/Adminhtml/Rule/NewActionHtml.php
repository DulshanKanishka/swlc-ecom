<?php

namespace Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;

use Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;

class NewActionHtml extends Rule
{
    public function execute()
    {
        $this->newConditions('actions');
    }
}
