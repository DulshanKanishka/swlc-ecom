<?php
namespace Magenest\Xero\Plugin\XmlLog;

use Magenest\Xero\Model\Parser;
use Magenest\Xero\Model\Synchronization;
use Magenest\Xero\Model\XmlLogFactory;
use Magenest\Xero\Model\Helper;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Sales\Model\Order;

class Save
{
    protected $_xmlLogFactory;

    protected $_helper;

    public function __construct(
        XmlLogFactory $xmlLogFactory,
        Helper $helper
    ){
        $this->_xmlLogFactory = $xmlLogFactory;
        $this->_helper = $helper;
    }

    public function afterAddRecord(Synchronization $subject, $result, $model, $credit = false)
    {
        $xmlLog = $this->_xmlLogFactory->create();
        $xml = $this->parseXML($result);
        if (isset($xml['Payment'])) {
            $xml = $xml['Payment'];
        }
        if (isset($xml[$subject->getSyncTypeKey()][$subject->getSyncIdKey()])) {
            $id = $xml[$subject->getSyncTypeKey()][$subject->getSyncIdKey()];
        } else if ($credit) {
            $id = $xml['CreditNote']['CreditNoteNumber'];
        } else {
            $id = $this->getIdByModel($model);
        }
        if ($id) {
            $xmlLog->setData([
                'magento_id' => $id,
                'xml_log' => $result,
                'type' => $subject->getType(),
                'scope' => $this->_helper->getScope(),
                'scope_id' => $this->_helper->getScopeId()
            ]);
            $xmlLog->save();
        }

        return $result;
    }

    public function afterAddOtherTags(Synchronization\BankTransaction $subject, $result)
    {
        $model = $this->_xmlLogFactory->create();
        $xml = $this->parseXML($result);

        if (isset($xml[$subject->getSyncTypeKey()])) {
            $id = "NONE";
            $model->setData([
                'magento_id' => $id,
                'xml_log' => $result,
                'type' => $subject->getType(),
                'scope' => $this->_helper->getScope(),
                'scope_id' => $this->_helper->getScopeId()
            ]);
            $model->save();
        }

        return $result;
    }

    public function afterAddCreditRecord(Synchronization\Payment $subject, $result, $model)
    {
        return $this->afterAddRecord($subject, $result, $model, true);
    }

    public function parseXML($xml)
    {
        $parser = new Parser();
        $result = $parser->parseXML($xml);

        return $result;
    }

    public function getIdByModel($model)
    {
        $id = false;
        if ($model instanceof Product)
            $id = $model->getSku();
        if ($model instanceof Order)
            $id = $model->getIncrementId();
        if ($model instanceof Order\Invoice)
            $id = $model->getIncrementId();
        if ($model instanceof Customer)
            $id = $model->getId();
        if ($model instanceof Order\Creditmemo)
            $id = 'C'.$model->getIncrementId();
        return $id;
    }
}