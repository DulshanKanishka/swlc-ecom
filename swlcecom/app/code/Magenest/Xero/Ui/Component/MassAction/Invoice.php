<?php
namespace Magenest\Xero\Ui\Component\MassAction;

use Magento\Ui\Component\MassAction;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Invoice extends MassAction
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

    protected function getInvoiceSyncConfig()
    {
        return [
            'component' => 'uiComponent',
            'type' => 'invoice_sync',
            'label' => 'Sync to Xero',
            'url' => $this->_url->getUrl('xero/massSync/invoice'),
            'confirm' => [
                'title' => 'Sync Invoice(s)',
                'message' => 'Are you sure you want to sync selected invoices?'
            ]
        ];
    }

    protected function getInvoiceAddToQueueConfig()
    {
        return [
            'component' => 'uiComponent',
            'type' => 'invoice_queue',
            'label' => 'Add to Queue',
            'url' => $this->_url->getUrl('xero/massQueue/invoice'),
            'confirm' => [
                'title' => 'Add Invoice(s) To Queue',
                'message' => 'Are you sure you want to add selected invoices to queue?'
            ]
        ];
    }

    public function prepare()
    {
        parent::prepare();
        if ($this->_config->getValue('magenest_xero_config/xero_order/invoice_enabled')) {
            $config = $this->getConfiguration();
            $config['actions'][] = $this->getInvoiceSyncConfig();
            $config['actions'][] = $this->getInvoiceAddToQueueConfig();
            $this->setData('config', (array)$config);
        }
    }
}