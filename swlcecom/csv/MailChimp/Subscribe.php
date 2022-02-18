<?php

namespace Neosolax\Email\Block\MailChimp;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;


class Subscribe extends Template
{
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    )

    {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    public function getStoreId()
    {
        return $this->storeManager->getStore()->getStoreId();
    }
}