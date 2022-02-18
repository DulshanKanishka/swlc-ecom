<?php
namespace Magenest\Xero\Ui\Component\MassAction;

use Magento\Ui\Component\MassAction;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Order extends MassAction
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

    protected function getOrderSyncConfig()
    {
        return [
            'component' => 'uiComponent',
            'type' => 'order_sync',
            'label' => 'Sync to Xero',
            'url' => $this->_url->getUrl('xero/massSync/order'),
            'confirm' => [
                'title' => 'Sync Order(s)',
                'message' => 'Are you sure you want to sync selected orders?'
            ]
        ];
    }

    protected function getOrderAddToQueueConfig()
    {
        return [
            'component' => 'uiComponent',
            'type' => 'order_queue',
            'label' => 'Add to Queue',
            'url' => $this->_url->getUrl('xero/massQueue/order'),
            'confirm' => [
                'title' => 'Add Order(s) To Queue',
                'message' => 'Are you sure you want to add selected orders to queue?'
            ]
        ];
    }

    public function prepare()
    {
        parent::prepare();
        if ($this->_config->getValue('magenest_xero_config/xero_order/order_enabled')) {
            $config = $this->getConfiguration();
            $config['actions'][] = $this->getOrderSyncConfig();
            $config['actions'][] = $this->getOrderAddToQueueConfig();
            $this->setData('config', (array)$config);
        }
    }
}