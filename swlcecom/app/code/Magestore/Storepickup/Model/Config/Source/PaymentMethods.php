<?php
namespace Magestore\Storepickup\Model\Config\Source;

class PaymentMethods implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    protected $_collectionFactory;
    public function __construct(
        \Magento\Payment\Model\Config $collectoryFactory
    )
    {
        $this->_collectionFactory = $collectoryFactory;
    }

    public function toOptionArray()
    {
        $storeCollection = $this->_collectionFactory->getActiveMethods();
        if(!count($storeCollection))return;

        $options = array() ;

        foreach($storeCollection as $item)
        {
            //var_dump($item);die();
            $title = $item->getTitle() ? $item->getTitle() : $item->getCode();
            $options[] = array('value'=> $item->getCode(), 'label' => $title);
        }

        return $options;
    }
}
