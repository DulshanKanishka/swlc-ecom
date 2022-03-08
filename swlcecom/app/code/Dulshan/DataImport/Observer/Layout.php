<?php
namespace Dulshan\DataImport\Observer;


use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;


class Layout implements ObserverInterface
{


    public function execute(Observer $observer)
    {
        $xml = $observer->getEvent()->getLayout()->getXmlString();
        /*$this->_logger->debug($xml);*/
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/layout_block.xml');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($xml);
        return $this;
    }
}

