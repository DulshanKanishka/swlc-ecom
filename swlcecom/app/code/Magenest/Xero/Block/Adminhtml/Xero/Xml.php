<?php
namespace Magenest\Xero\Block\Adminhtml\Xero;

use Magenest\Xero\Model\TaxMappingFactory;
use Magenest\Xero\Model\XeroClient;
use Magento\Tax\Model\Calculation\Rate;
/**
 * Class Payment
 * @package Magenest\Xero\Block\Adminhtml\Xero
 */
class Xml extends \Magento\Backend\Block\Widget
{

    protected $xeroClient;

    protected $taxModelConfig;

    protected $mappingFactory;

    protected $_cache;
    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        XeroClient $xeroClient,
        TaxMappingFactory $mappingFactory,
        Rate $taxModelConfig,
        array $data = []
    ) {
        $this->xeroClient = $xeroClient;
        $this->mappingFactory = $mappingFactory;
        $this->taxModelConfig = $taxModelConfig;
        parent::__construct($context, $data);
    }

    public function getTaxRates()
    {
        $taxRates = $this->taxModelConfig->getCollection()->getData();
        $methods = array();
        foreach ($taxRates as $tax) {
            $methods[$tax['code']] = $tax;
        }
        return $methods;
    }
}
