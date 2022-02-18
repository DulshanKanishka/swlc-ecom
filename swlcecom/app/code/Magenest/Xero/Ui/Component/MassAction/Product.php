<?php
namespace Magenest\Xero\Ui\Component\MassAction;

use Magento\Ui\Component\MassAction;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Product extends MassAction
{
    protected $_url;

    protected $_config;

    public function __construct(
        ContextInterface $context,
        UrlInterface $url,
        ScopeConfigInterface $config,
        $components,
        array $data
    ) {
        $this->_url = $url;
        $this->_config = $config;
        parent::__construct($context, $components, $data);
    }

    protected function getProductSyncConfig()
    {
        return [
            'component' => 'uiComponent',
            'type' => 'item_sync',
            'label' => 'Sync to Xero',
            'url' => $this->_url->getUrl('xero/massSync/item'),
            'confirm' => [
                'title' => 'Sync Product(s)',
                'message' => 'Are you sure you want to sync selected products?'
            ]
        ];
    }

    protected function getProductAddToQueueConfig()
    {
        return [
            'component' => 'uiComponent',
            'type' => 'item_queue',
            'label' => 'Add to Queue',
            'url' => $this->_url->getUrl('xero/massQueue/item'),
            'confirm' => [
                'title' => 'Add Item(s) To Queue',
                'message' => 'Are you sure you want to add selected items to queue?'
            ]
        ];
    }

    public function prepare()
    {
        parent::prepare();
        if ($this->_config->getValue('magenest_xero_config/xero_item/enabled')) {
            $config = $this->getConfiguration();
            $config['actions'][] = $this->getProductSyncConfig();
            $config['actions'][] = $this->getProductAddToQueueConfig();
            $this->setData('config', (array)$config);
        }
    }
}