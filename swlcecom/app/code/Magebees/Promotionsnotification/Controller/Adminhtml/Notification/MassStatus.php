<?php
namespace Magebees\Promotionsnotification\Controller\Adminhtml\Notification;

class MassStatus extends \Magento\Backend\App\Action
{
    public function execute()
    {
        $notificationIds = $this->getRequest()->getParam('notification');
        if (!is_array($notificationIds) || empty($notificationIds)) {
            $this->messageManager->addError(__('Please select notification(s).'));
        } else {
            try {
                foreach ($notificationIds as $notificationId) {
                    $model = $this->_objectManager->get('Magebees\Promotionsnotification\Model\Promotionsnotification')->load($notificationId);
                    $model->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->messageManager->addSuccess(
                    __('Total of %1 record(s) were successfully updated.', count($notificationIds))
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
