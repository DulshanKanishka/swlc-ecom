<?php

namespace Sm\Aloza\Controller\Adminhtml\System\Config;

abstract class Cms extends \Magento\Backend\App\Action {
    protected function _import()
    {
        return $this->_objectManager->get('Sm\Aloza\Model\Import\Cms')
            ->importCms($this->getRequest()->getParam('import_type'));
    }
}
