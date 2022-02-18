<?php

namespace Meetanshi\PaymentRestriction\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Meetanshi\PaymentRestriction\Model\RuleFactory;
use Meetanshi\PaymentRestriction\Model\ResourceModel\Rule\CollectionFactory;

abstract class Rule extends Action
{
    protected $coreRegistry;
    protected $resultForwardFactory;
    protected $resultPageFactory;
    protected $ruleFactory;
    protected $ruleCollectionFactory;

    public function __construct(Context $context, Registry $coreRegistry, ForwardFactory $resultForwardFactory, PageFactory $resultPageFactory, RuleFactory $ruleFactory, CollectionFactory $ruleCollectionFactory)
    {
        $this->ruleFactory = $ruleFactory;
        $this->coreRegistry = $coreRegistry;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        parent::__construct($context);
    }

    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Meetanshi_PaymentRestriction::rule')->_addBreadcrumb(__('Payment Restrictions'), __('Payment Restrictions'));
        return $this;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Meetanshi_PaymentRestriction::rule');
    }


    public function newConditions($prefix)
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = $this->_objectManager->create($type)->setId($id)->setType($type)->setRule($this->_objectManager->create('Magento\CatalogRule\Model\Rule'))->setPrefix($prefix);
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof AbstractCondition) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }
}
