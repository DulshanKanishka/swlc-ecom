<?php
namespace Magebees\Promotionsnotification\Block\Adminhtml\Promotionsnotification\Edit\Tab;

class Code extends \Magento\Backend\Block\Widget\Form\Generic
{
    protected $_template = 'code.phtml';
    
    /**
     * Prepare form
     *
     * @return $this
     */
    
    public function getNotificationData()
    {
        $model = $this->_coreRegistry->registry('notification_data');
        return $model->getNotificationId();
    }
    
    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
