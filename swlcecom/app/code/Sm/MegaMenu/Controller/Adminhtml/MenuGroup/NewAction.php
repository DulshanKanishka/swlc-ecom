<?php
/*------------------------------------------------------------------------
# SM Mega Menu - Version 3.2.0
# Copyright (c) 2015 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\MegaMenu\Controller\Adminhtml\MenuGroup;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Backend\Model\View\Result\ForwardFactory;

//class NewAction extends \Sm\MegaMenu\Controller\Adminhtml\MenuGroup
class NewAction extends \Magento\Backend\App\Action
{
    protected $resultForwardFactory;

    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory
    )
    {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        if (!isset($_POST['isAjax']) && !isset($_GET['isAjax'])) {
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('edit');
        }
    }
}
