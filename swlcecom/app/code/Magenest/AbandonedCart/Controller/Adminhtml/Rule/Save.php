<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Rule;

use Magenest\AbandonedCart\Helper\Data;
use function Sodium\compare;

class Save extends \Magenest\AbandonedCart\Controller\Adminhtml\Rule
{

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $params         = $this->_request->getParams();

        $redirectBack = $this->_request->getParam('back', false);
        try {
            /** @var \Magenest\AbandonedCart\Model\Rule $ruleModel */
            $ruleModel = $this->_ruleFactory->create();
            if ($id = $this->_request->getParam('rule_id')) {
                $data['id'] = $id;
            }
            $data['name']                  = isset($params['name']) ? $params['name'] : '';
            $data['description']           = isset($params['description']) ? $params['description'] : '';
            $data['status']                = isset($params['status']) ? $params['status'] : '';
            $data['from_date']             = isset($params['from_date']) ? $params['from_date'] : '';
            $data['to_date']               = isset($params['to_date']) ? $params['to_date'] : '';
            $data['stores_view']           = isset($params['stores_view']) ? json_encode($params['stores_view']) : '';
            $data['customer_group']        = isset($params['customer_group']) ? json_encode($params['customer_group']) : '';
            $data['discard_subsequent']    = isset($params['discard_subsequent']) ? $params['discard_subsequent'] : 0;
            $data['priority']              = isset($params['priority']) ? $params['priority'] : 0;
            $data['cancel_rule_when']      = isset($params['cancel_rule_when']) ? json_encode($params['cancel_rule_when']) : '';
            $data['conditions_serialized'] = $this->getConditionsToSave();
            $data['email_chain']           = isset($params['email_chain']) ? json_encode($params['email_chain']) : '';
            $data['sms_chain']             = isset($params['sms_chain']) ? json_encode($params['sms_chain']) : '';
            $data['ga_source']             = isset($params['ga_source']) ? $params['ga_source'] : '';
            $data['ga_medium']             = isset($params['ga_medium']) ? $params['ga_medium'] : '';
            $data['ga_name']               = isset($params['ga_name']) ? $params['ga_name'] : '';
            $data['ga_term']               = isset($params['ga_term']) ? $params['ga_term'] : '';
            $data['ga_content']            = isset($params['ga_content']) ? $params['ga_content'] : '';

            // process the attached files
            $attachedData        = $this->getRequest()->getParam('product');
            $refinedAttachedData = [];
            if (is_array($attachedData) && !empty($attachedData)) {
                foreach ($attachedData as $attach) {
                    foreach ($attach['images'] as $images) {
                        if ($images['removed'] == 1) continue;
                        $refinedAttachedData[] = $images;
                    }
                }
            }
            $data['attached_files'] = json_encode($refinedAttachedData);
            $ruleModel->setData($data);
            if ($data['from_date'] != "" && $this->filter($data['from_date'])) {
                throw new \Exception($this->filter($data['from_date']));
            }
            if ($data['to_date'] != "" && $this->filter($data['to_date'])) {
                throw new \Exception($this->filter($data['to_date']));
            }
            $ruleModel->save();


            if ($this->validDateFromTo($data['from_date'], $data['to_date'])) {
                $ruleModel->setStatus(0);
                $ruleModel->save();
                throw new \Exception($this->validDateFromTo($data['from_date'], $data['to_date']));
            }
//            if($this->checkPriority($data,$ruleModel->getId())){
//                $ruleModel->setStatus(0);
//                $ruleModel->save();
//                throw new \Exception(__('The priority of rules should not overlap, please choose another priority'));
//            }
            $this->messageManager->addSuccessMessage(__('You saved the Abandoned Cart rule.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->_logger->critical($e->getMessage());
        }
        if ($redirectBack === 'edit') {
            $resultRedirect->setPath(
                '*/*/edit',
                ['id' => $ruleModel->getId(), 'back' => null, '_current' => true]
            );
        } else {
            $resultRedirect->setPath('*/*/index');
        }
        return $resultRedirect;
    }

    public function validDateFromTo($from, $to)
    {
        if ($from == '' || $to == '') {
            return false;
        } else {
            $timestampFrom = $this->_dateTime->timestamp($from);
            $timestampTo   = $this->_dateTime->timestamp($to);
            if ($timestampFrom > $timestampTo) {
                return __('Start Date must not be later than End Date');
            } else {
                return false;
            }
        }
    }

    public function filter($value)
    {
        $time = strtotime($value);
        if (!$time) {
            return __("Invalid input datetime format of value '$value'");
        } else {
            return false;
        }
    }

    public function getConditionsToSave()
    {
        $catalogRule = $this->_objectManager->create('\Magento\SalesRule\Model\Rule');
        $params      = $this->_request->getParams();
        $catalogRule->loadPost($params['rule']);
        $asArray        = $catalogRule->getConditions()->asArray();
        $versionMagento = $this->_helperData->getVersionMagento();
        if (version_compare($versionMagento, '2.2.0') < 0) {
            $conditionsSerializedString = serialize($asArray);
        } else {
            $conditionsSerializedString = json_encode($asArray);
        }
        return $conditionsSerializedString;
    }

    public function checkPriority($data, $currentId)
    {
        $priority = $data['priority'];
        if (isset($data['id']) && (int)$id = $data['id']) {
            $ruleModel = $this->_ruleFactory->create()->getCollection()
                ->addFieldToFilter(
                    'id',
                    ['neq' => $id]
                )
                ->addFieldToFilter('priority', $priority)
                ->getFirstItem();
        } else {
            $ruleModel = $this->_ruleFactory->create()->getCollection()
                ->addFieldToFilter('priority', $priority)
                ->getFirstItem();
        }
        if ($ruleModel->getId() != $currentId) {
            return true;
        } else {
            return false;
        }
    }
}