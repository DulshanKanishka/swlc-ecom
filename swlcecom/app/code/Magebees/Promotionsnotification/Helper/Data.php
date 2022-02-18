<?php
namespace Magebees\Promotionsnotification\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $filterProvider;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider
       
    ) {
        parent::__construct($context);
        $this->filterProvider = $filterProvider;
    }
    
    public function getFilterProvider()
    {
        return $this->filterProvider;
    }
}
