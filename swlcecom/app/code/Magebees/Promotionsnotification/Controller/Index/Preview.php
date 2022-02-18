<?php
namespace Magebees\Promotionsnotification\Controller\Index;

use \Magento\Framework\App\Action\Action;

class Preview extends Action
{
    
    
    /**
     * Dispatch request
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    
    
    public function execute()
    {
        $resultFactory= $this->_objectManager->create('\Magento\Framework\View\Result\PageFactory');
        $resultPage= $resultFactory->create();
        $layoutblk = $resultPage->addHandle('promptions_notification_preview')->getLayout();
        $result= $layoutblk->getBlock('notification_preview')->toHtml();
                
        $this->getResponse()->representJson(
            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
        );
    }
}
