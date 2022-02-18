<?php
namespace Magenest\Xero\Ui\Component\MassAction;

use Magento\Ui\Component\MassAction;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CreditMemo extends MassAction
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

    protected function getCreditMemoSyncConfig()
    {
        return [
            'component' => 'uiComponent',
            'type' => 'credit_sync',
            'label' => 'Sync to Xero',
            'url' => $this->_url->getUrl('xero/massSync/creditmemo'),
            'confirm' => [
                'title' => 'Sync Credit Memo(s)',
                'message' => 'Are you sure you want to sync selected credit memos?'
            ]
        ];
    }

    protected function getCreditMemoAddToQueueConfig()
    {
        return [
            'component' => 'uiComponent',
            'type' => 'credit_queue',
            'label' => 'Add to Queue',
            'url' => $this->_url->getUrl('xero/massQueue/creditmemo'),
            'confirm' => [
                'title' => 'Add Credit Memo(s) To Queue',
                'message' => 'Are you sure you want to add selected credit memos to queue?'
            ]
        ];
    }

    public function prepare()
    {
        parent::prepare();
        if ($this->_config->getValue('magenest_xero_config/xero_credit/enabled')) {
            $config = $this->getConfiguration();
            $config['actions'][] = $this->getCreditMemoSyncConfig();
            $config['actions'][] = $this->getCreditMemoAddToQueueConfig();
            $this->setData('config', (array)$config);
        }
    }
}