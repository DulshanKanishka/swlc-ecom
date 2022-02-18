<?php

namespace Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;

use Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;

class NewConditionHtml extends Rule
{
    public function execute()
    {
        $this->newConditions('conditions');
    }
}
