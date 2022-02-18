<?php
namespace Magenest\Xero\Controller\Adminhtml\Payment;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\PaymentMappingFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class SaveMapping extends \Magento\Backend\App\Action
{
    protected $paymentMappingFactory;

    protected $xeroHelper;

    public function __construct(
        Context $context,
        PaymentMappingFactory $paymentMappingFactory,
        Helper $helper
    ) {
        parent::__construct($context);
        $this->paymentMappingFactory = $paymentMappingFactory;
        $this->xeroHelper = $helper;
    }

    public function execute()
    {
        try {
            $mappingModel = $this->paymentMappingFactory->create();
            $params = $this->getRequest()->getPostValue();
            if (isset($params['paymentMapping'])) {
                if (isset($params['website_id']) && $params['website_id'] > 0) {
                    $this->xeroHelper->setScope('websites');
                    $this->xeroHelper->setScopeId($params['website_id']);
                }
                foreach ($params['paymentMapping'] as $paymentCode => $bankAccId) {
                    $mapping = $mappingModel->loadByPaymentCode($paymentCode);
                    if ($mapping) {
                        $bankAccId = $bankAccId == 'null' ? null : $bankAccId;
                        $mapping->setBankAccountId($bankAccId);
                        $mapping->save();
                        $mapping->unsetData();
                    } else {
                        $bankAccId = $bankAccId == 'null' ? null : $bankAccId;
                        $mappingData = [
                            'payment_code' => $paymentCode,
                            'bank_account_id' => $bankAccId,
                            'updated_at' => time()
                        ];
                        $mappingModel->setData($mappingData)->save();
                        $mappingModel->unsetData();
                    }
                }
                $result = [
                    'error' => 0,
                    'msg' => 'Save Payment Mapping Successfully'
                ];
            } else {
                $result = [
                    'error' => 1,
                    'msg' => 'Can not get Mapping Field',
                    'params' => $params
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'error' => 1,
                'msg' => $e->getMessage(),
                'params' => $this->getRequest()->getPostValue()
            ];
        }
        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $jsonResult->setData($result);
        return $jsonResult;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::xero');
    }
}
