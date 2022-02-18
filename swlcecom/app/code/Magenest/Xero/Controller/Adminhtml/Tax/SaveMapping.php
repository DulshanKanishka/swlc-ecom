<?php
namespace Magenest\Xero\Controller\Adminhtml\Tax;

use Magenest\Xero\Model\Helper;
use Magenest\Xero\Model\TaxMappingFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class SaveMapping extends \Magento\Backend\App\Action
{
    protected $taxMappingFactory;

    protected $xeroHelper;

    public function __construct(
        Context $context,
        TaxMappingFactory $taxMappingFactory,
        Helper $helper
    ) {
        parent::__construct($context);
        $this->taxMappingFactory = $taxMappingFactory;
        $this->xeroHelper = $helper;
    }

    public function execute()
    {
        try {
            $mappingModel = $this->taxMappingFactory->create();
            $params = $this->getRequest()->getPostValue();
            if (isset($params['taxRateMapping'])) {
                if (isset($params['website_id']) && $params['website_id'] > 0) {
                    $this->xeroHelper->setScope('websites');
                    $this->xeroHelper->setScopeId($params['website_id']);
                }
                foreach ($params['taxRateMapping'] as $taxCode => $xeroTaxCode) {
                    $mapping = $mappingModel->loadByTaxCode($taxCode);
                    if ($mapping) {
                        $xeroTaxCode = $xeroTaxCode == 'null' ? null : $xeroTaxCode;
                        $mapping->setXeroTaxCode($xeroTaxCode);
                        $mapping->save();
                        $mapping->unsetData();
                    } else {
                        $xeroTaxCode = $xeroTaxCode == 'null' ? null : $xeroTaxCode;
                        $mappingData = [
                            'tax_id' => $taxCode,
                            'xero_tax_code' => $xeroTaxCode,
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
