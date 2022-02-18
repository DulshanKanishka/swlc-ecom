<?php
namespace Magebees\Promotionsnotification\Model;

class Promotionsnotification extends \Magento\Framework\Model\AbstractModel
{

    protected $_date;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_date = $date;
        //$this->coreRegistry = $registry;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }
     
    
    /**
     * Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magebees\Promotionsnotification\Model\ResourceModel\Promotionsnotification');
    }
    
    public function validateDate($object)
    {
        $fromDate = $object['from_date'];
        $toDate = $object['to_date'];
        if ($fromDate != "" && $toDate != "") {
            $date = $this->_date;
            $value = $date->timestamp($fromDate);
            $maxValue = $date->timestamp($toDate);
            if ($value > $maxValue) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }
}
