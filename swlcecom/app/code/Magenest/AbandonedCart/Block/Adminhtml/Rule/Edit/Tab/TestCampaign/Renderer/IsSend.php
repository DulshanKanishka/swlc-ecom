<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\Rule\Edit\Tab\TestCampaign\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

class IsSend extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($row['status'] == "0") {
            $status = 'QUEUED';
        } elseif ($row['status'] == "2") {
            $status = 'SENT';
        } elseif ($row['status'] == "3") {
            $status = 'FAILED';
        } else {
            $status = 'CANCELLED';
        }
        return $status;
    }
}