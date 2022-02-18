<?php

namespace Magenest\AbandonedCart\Controller\Adminhtml\Rule;

use Magento\Framework\App\Filesystem\DirectoryList;

class Upload extends \Magenest\AbandonedCart\Controller\Adminhtml\Rule
{

    public function execute()
    {
        try {
            /** @var  $uploader \Magento\MediaStorage\Model\File\Uploader */
            $uploader = $this->_objectManager->create(
                'Magento\MediaStorage\Model\File\Uploader',
                ['fileId' => 'image']
            );
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'pdf', 'doc', 'docx', 'txt']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
            $mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')->getDirectoryRead(DirectoryList::MEDIA);
            $config         = $this->_objectManager->get('Magento\Catalog\Model\Product\Media\Config');
            $result         = $uploader->save($mediaDirectory->getAbsolutePath($config->getBaseTmpMediaPath()));

            $this->_eventManager->dispatch(
                'ufe_attach_upload_after',
                [
                    'result' => $result,
                    'action' => $this,
                ]
            );
            $anaName     = explode('/', $result['file']);
            $desireIndex = (count($anaName) - 1);

            $result['label'] = $anaName[$desireIndex];
            unset($result['tmp_name']);
            unset($result['path']);

            $result['url'] = $this->_objectManager->get('Magento\Catalog\Model\Product\Media\Config')->getTmpMediaUrl($result['file']);
        } catch (\Exception $exception) {
            $result = [
                'error'     => $exception->getMessage(),
                'errorcode' => $exception->getCode(),
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Raw $response */
        $response = $this->resultRawFactory->create();
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(json_encode($result));
        return $response;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_AbandonedCart::rule');
    }
}