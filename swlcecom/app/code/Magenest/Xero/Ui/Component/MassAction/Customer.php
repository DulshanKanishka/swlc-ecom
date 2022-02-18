<?php
namespace Magenest\Xero\Ui\Component\MassAction;

use Magento\Ui\Component\MassAction;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Customer extends MassAction
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

    protected function getCustomerSyncConfig()
    {
        return [
            'component' => 'uiComponent',
            'type' => 'customer_sync',
            'label' => 'Sync to Xero',
            'url' => $this->_url->getUrl('xero/massSync/customer'),
            'confirm' => [
                'title' => 'Sync Customer(s)',
                'message' => 'Are you sure you want to sync selected customers?'
            ]
        ];
    }

    protected function getCustomerAddToQueueConfig()
    {
        return [
            'component' => 'uiComponent',
            'type' => 'customer_queue',
            'label' => 'Add to Queue',
            'url' => $this->_url->getUrl('xero/massQueue/customer'),
            'confirm' => [
                'title' => 'Add Customer(s) To Queue',
                'message' => 'Are you sure you want to add selected customer to queue?'
            ]
        ];
    }

    public function prepare()
    {
        parent::prepare();
        if ($this->_config->getValue('magenest_xero_config/xero_contact/enabled')) {
            $config = $this->getConfiguration();
            $config['actions'][] = $this->getCustomerSyncConfig();
            $config['actions'][] = $this->getCustomerAddToQueueConfig();
            $this->setData('config', (array)$config);
        }
    }
}