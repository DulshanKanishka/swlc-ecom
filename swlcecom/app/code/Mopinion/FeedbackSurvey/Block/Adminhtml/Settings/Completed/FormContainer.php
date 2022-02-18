<?php

namespace Mopinion\FeedbackSurvey\Block\Adminhtml\Settings\Completed;

/**
 * The admin-specific functionality of the extension.
 *
 * @link       https://mopinion.com/author/kees-wolters/
 * @since      1.0.0
 *
 * @package    Mopinion_FeedbackSurvey
 */

class FormContainer extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Mopinion\FeedbackSurvey\Helper\Data $mopinionData,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->mopinionData = $mopinionData;
        parent::__construct($context, $data);
    }

    /**
     * Init class
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_settings_completed';
        $this->_blockGroup = 'Mopinion_FeedbackSurvey';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save'));
        $this->removeButton('reset');
        $this->removeButton('back');
    }

    /**
     * Get Header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Edit mopinion feedback survey');
    }

    /**
     * Prepare form Html. call the phtm file with form.
     *
     * @return string
     */
    public function getFormHtml()
    {
        $this->mopinionKey = $this->mopinionData->getMopinionKey();
        $this->email = $this->mopinionData->getMopinionAccountEmail();
        $this->placement = $this->mopinionData->getPosition();

        // get the current form as html content.
        $html = parent::getFormHtml();
        $para = $this->setTemplate('Mopinion_FeedbackSurvey::completed_info.phtml')->toHtml();
        $footer = $this->setTemplate('Mopinion_FeedbackSurvey::completed_footer.phtml')
            ->toHtml();

        //Inject the phtml file after the legend.
        $html = str_replace('</legend>', '</legend>' . $para, $html);
        return $html . $footer;
    }
}
