<?php
namespace Magebees\Promotionsnotification\Controller\Adminhtml\Notification;

class Save extends \Magento\Backend\App\Action
{
    
    public function execute()
    {
        $data = $this->getRequest()->getPost()->toArray();
       // print_R($data);exit;
        $id = $this->getRequest()->getParam('notification_id');
        $jsHelper = $this->_objectManager->create('Magento\Backend\Helper\Js');
        if ($data) {
            if (isset($data['links'])) {
                $data['product_sku'] = $jsHelper->decodeGridSerializedInput($data['links']['notification']);
            } else {
                $model_product = $this->_objectManager->create('Magebees\Promotionsnotification\Model\Product')->getCollection()
                ->addFieldToFilter('notification_id', ['eq' => $id]);
                $data['product_sku']=array_map([$this,"getProdcutSkusArr"], $model_product->getData());
            }
            $model = $this->_objectManager->create('Magebees\Promotionsnotification\Model\Promotionsnotification');
            
            if ($id) {
                $model->load($id);
            }
                    
            try {
                //validate from and to date
                $validateResult = $model->validateDate($data);
                if ($validateResult == false) {
                    $this->messageManager->addError(__('To Date must be greater than From Date.'));
                    $this->_redirect('*/*/edit', ['id' => $model->getNotificationId(), '_current' => true]);
                    return;
                }
                
                if (in_array("0", $data['stores'])) {
                    $storeids = [];
                    $storeids[0] = 0;
                    $data['stores'] = $storeids;
				}

               
                if(array_key_exists('pages', $data)){
                    if (in_array("0", $data['pages'])) {
                        $pagesids = [];
                        $pagesids[0] = 0;
                        $data['pages'] = $pagesids;
                    }
                }

                if(array_key_exists('category_ids', $data)){
                    if (in_array("0", $data['category_ids'])) {
                        $category_ids = [];
                        $category_ids[0] = 0;
                        $data['category_ids'] = $category_ids;
                    }
                }

                
				
				/*if (in_array("0", $data['stores'])) {
                    $allStores = $this->_objectManager->create('\Magento\Store\Model\StoreManagerInterface')->getStores();
                    $storeids = [];
                    //$storeids[0] = 0;
                    foreach ($allStores as $_eachStoreId => $val) {
                        $_storeId = $this->_objectManager->create('\Magento\Store\Model\StoreManagerInterface')->getStore($_eachStoreId)->getId();
                        $storeids[] = $_storeId;
                    }
                    $data['stores'] = $storeids;
                }*/
				
				$model->setData($data);
                $model->save();
                $this->messageManager->addSuccess(__('Notification was successfully saved'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['id' => $model->getNotificationId(), '_current' => true]);
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (\Magento\Framework\Model\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                //$this->messageManager->addException($e, __('Something went wrong while saving the Notification.'));
                $this->messageManager->addError($e->getMessage());
            }

            $this->_getSession()->setFormData($data);
            $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('notification_id')]);
            return;
        }
        $this->_redirect('*/*/');
    }
    
    public function getProdcutSkusArr($element)
    {
        return $element['product_sku'];
    }
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebees_Promotionsnotification::promotions_content');
    }
}
