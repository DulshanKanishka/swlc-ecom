<?php
namespace Magenest\Xero\Model;

/**
 * Class Log
 * @package Magenest\Xero\Model
 */
class XmlLog extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Init
     */
    protected $_helper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Helper $helper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ){
        $this->_helper = $helper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Magenest\Xero\Model\ResourceModel\XmlLog');
    }

    public function afterSave()
    {
        $this->_helper->addSavedId($this->getMagentoId(), $this->getId(), $this->getType());
        return parent::afterSave(); // TODO: Change the autogenerated stub
    }
}