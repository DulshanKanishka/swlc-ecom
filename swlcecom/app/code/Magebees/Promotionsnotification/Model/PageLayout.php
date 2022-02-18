<?php
namespace Magebees\Promotionsnotification\Model;

class PageLayout implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface
     */
    protected $pageLayoutBuilder;

    /**
     * @param \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface $pageLayoutBuilder
     */
    public function __construct(\Magento\Framework\View\Model\PageLayout\Config\BuilderInterface $pageLayoutBuilder)
    {
        $this->pageLayoutBuilder = $pageLayoutBuilder;
    }
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->pageLayoutBuilder->getPageLayoutsConfig()->toOptionArray();
    }
}
