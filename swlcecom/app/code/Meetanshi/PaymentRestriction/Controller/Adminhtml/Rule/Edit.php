<?php

namespace Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;

use Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;
use Meetanshi\PaymentRestriction\Model\Rule as PayRule;

class Edit extends Rule
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->ruleFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This item no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }

        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        } else {
            $this->_prepareForEdit($model);
        }
        $this->coreRegistry->register('current_paymentrestriction_rule', $model);
        $this->_initAction();
        if ($model->getId()) {
            $title = __('Edit Payment Restrictions Rule `%1`', $model->getName());
        } else {
            $title = __("Add new Payment Restriction Rule");
        }
        $this->_view->getPage()->getConfig()->getTitle()->prepend($title);
        $this->_view->renderLayout();
    }

    public function _prepareForEdit(PayRule $model)
    {
        $fields = ['stores', 'cust_groups', 'methods'];
        foreach ($fields as $field) {
            $val = $model->getData($field);
            if (!is_array($val)) {
                $model->setData($field, explode(',', $val));
            }
        }

        $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
        return true;
    }
}
