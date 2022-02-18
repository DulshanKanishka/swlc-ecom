<?php
namespace Magebees\Promotionsnotification\Controller\Adminhtml\Notification;

class MassDelete extends \Magento\Backend\App\Action
{
    protected $aclRetriever;
    protected $authSession;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Authorization\Model\Acl\AclRetriever $aclRetriever,
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        parent::__construct($context);
        $this->aclRetriever = $aclRetriever;
        $this->authSession = $authSession;
    }
    
    public function execute()
    {
        $user = $this->authSession->getUser();
        $role = $user->getRole();
        $resources = $this->aclRetriever->getAllowedResourcesByRole($role->getId());
        if ($role->getRoleName()=="Promotions") {
            $this->messageManager->addNotice(__('This is demo store so you are not allowed to update details.'));
            $this->_redirect('*/*/index');
            return '0';
        }
        
        $notificationIds = $this->getRequest()->getParam('notification');
        
        if (!is_array($notificationIds) || empty($notificationIds)) {
            $this->messageManager->addError(__('Please select notification(s).'));
        } else {
            try {
                foreach ($notificationIds as $notificationId) {
                    $model = $this->_objectManager->get('Magebees\Promotionsnotification\Model\Promotionsnotification')->load($notificationId);
                    $model->delete();
                }
                        
                    $this->messageManager->addSuccess(
                        __('A total of %1 record(s) have been deleted.', count($notificationIds))
                    );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
         $this->_redirect('*/*/');
    }
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Promotionsnotification::promotions_content');
    }
}
