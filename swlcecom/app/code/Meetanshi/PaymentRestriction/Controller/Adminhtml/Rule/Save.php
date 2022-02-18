<?php
namespace Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;

use Meetanshi\PaymentRestriction\Controller\Adminhtml\Rule;
use Magento\Framework\Exception\LocalizedException;

class Save extends Rule
{
    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            try {
                $model = $this->ruleFactory->create();
                $data = $this->getRequest()->getPostValue();
                $inputFilter = new \Zend_Filter_Input(
                    [],
                    [],
                    $data
                );
                $data = $inputFilter->getUnescaped();
                $id = $this->getRequest()->getParam('id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new LocalizedException(__('The wrong item is specified.'));
                    }
                }
                if (isset($data['rule']['conditions'])) {
                    $data['conditions'] = $data['rule']['conditions'];
                }
                unset($data['rule']);

                $model->addData($data);
                $model->loadPost($data);
                $this->_prepareRuleForSave($model);

                $session = $this->_objectManager->get('Magento\Backend\Model\Session');
                $session->setPageData($model->getData());
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the rule.'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['id' => $model->getId()]);
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $id = (int)$this->getRequest()->getParam('id');
                if (!empty($id)) {
                    $this->_redirect('*/*/edit', ['id' => $id]);
                } else {
                    $this->_redirect('*/*/new');
                }
                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(_($e->getMessage()));

                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    protected function _prepareRuleForSave($model)
    {
        $fields = ['stores', 'cust_groups', 'methods'];
        foreach ($fields as $field) {
            $val = $model->getData($field);
            $model->setData($field, '');
            if (is_array($val)) {
                $model->setData($field, ',' . implode(',', $val) . ',');
            }
        }
        return true;
    }
}
