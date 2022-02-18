<?php
namespace Magenest\Xero\Controller\Adminhtml\Xml;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;
use Magenest\Xero\Model\XmlLogFactory;

/**
 * Class Index
 * @package Magenest\Xero\Controller\Adminhtml\Request
 */
class Index extends \Magento\Backend\App\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $pageFactory;

    protected $_xmlLogFactory;
    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param XmlLogFactory $xmlLogFactory
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        XmlLogFactory $xmlLogFactory
    ) {
        $this->pageFactory = $pageFactory;
        $this->_xmlLogFactory = $xmlLogFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('xml_log_id');
        $xmlLog = $this->_xmlLogFactory->create()->load($id);
        if (!$xmlLog->getId()) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $result->setPath('xero/log/');
        }
        /** @var \Magento\Framework\Controller\Result\Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('Content-type', 'application/xml');
        $result->setContents($xmlLog->getXmlLog());

        return $result;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_Xero::log');
    }
}
