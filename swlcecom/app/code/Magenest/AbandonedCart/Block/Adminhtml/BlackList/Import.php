<?php

namespace Magenest\AbandonedCart\Block\Adminhtml\BlackList;

class Import extends \Magento\Backend\Block\Template
{

    /** @var \Magento\Framework\View\Asset\Repository $assetRepository */
    protected $assetRepository;

    /**
     * Import constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->assetRepository = $context->getAssetRepository();
        parent::__construct($context, $data);
    }

    public function getFileSample()
    {
        /** @var \Magento\Framework\View\Asset\Repository $asset * */
        $asset = $this->assetRepository->createAsset('Magenest_AbandonedCart::file/blacklist.csv');
        return $asset->getFilePath();
    }
}