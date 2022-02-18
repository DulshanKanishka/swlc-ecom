<?php
/**
 * The admin-specific functionality of the extension.
 *
 * @link       https://mopinion.com/author/kees-wolters/
 * @since      1.0.0
 * @author     Kees Wolters
 * @package    Mopinion_FeedbackSurvey
 */

namespace Mopinion\FeedbackSurvey\Block;

use Magento\Framework\App\ObjectManager;

/**
 * Mopinion scripts Block
 *
 * @api
 * @since 100.0.2
 */
class Scripts extends \Magento\Framework\View\Element\Template
{
    /**
     * Mopinion analytics data
     *
     * @var \Mopinion\FeedbackSurvey\Helper\Data
     */
    protected $mopinionData = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Mopinion\FeedbackSurvey\Helper\Data $mopinionData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Mopinion\FeedbackSurvey\Helper\Data $mopinionData,
        array $data = []
    ) {
        $this->mopinionData = $mopinionData;
        parent::__construct($context, $data);
    }

    /**
     * Get config
     *
     * @param string $path
     * @return mixed
     */
    public function getConfig($path)
    {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Render mopinion tracking scripts
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->mopinionData->getMopinionKey() ||
            $this->mopinionData->getPosition() != 'mopinioninfooter') {
            return '';
        }
        return parent::_toHtml();
    }
}
