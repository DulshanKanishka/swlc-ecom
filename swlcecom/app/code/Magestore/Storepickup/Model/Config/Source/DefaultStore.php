<?php
namespace Magestore\Storepickup\Model\Config\Source;

class DefaultStore implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    protected $_collectionFactory;
    public function __construct(
        \Magestore\Storepickup\Model\ResourceModel\Store\CollectionFactory $collectoryFactory
    )
    {
        $this->_collectionFactory = $collectoryFactory;
    }

    public function toOptionArray()
    {
        $storeCollection = $this->_collectionFactory->create();
        $storeCollection = $storeCollection->addFieldToFilter('status','1');
        $arr = array();
        if($storeCollection->count()=='1') {
            foreach ($storeCollection as $item) {
                $arr[] = array('value' => $item->getId(), 'label' => $item->getStoreName());
            }

            return $arr;
        }

        $arr [] = array('value' => 0, 'label' => '---Choose Default Store---');
        foreach ($storeCollection as $item) {
            $arr[] = array('value' => $item->getId(), 'label' => $item->getStoreName());
        }
        //Zend_debug::dump($arr);die();
        return $arr;
    }
}
