<?php
namespace Dulshan\CustomComment\Block;

use Magento\Framework\View\Element\Template\Context;

class Comment extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
    public function toHtml()
    {
        return parent::toHtml();
    }
    public function getFeedbackUrl()
    {
        return $this->getUrl('comment/index/index');
    }
}
