<?php

namespace Mopinion\FeedbackSurvey\Block\Adminhtml\Settings\Edit;

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
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\User\Model\UserFactory $userFactory,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->_authSession = $authSession;
        $this->_userFactory = $userFactory;
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
        $this->_controller = 'adminhtml_settings_edit';
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
       // get the current form as html content.
        $userId = $this->_authSession->getUser()->getId();
        $this->email = $this->_userFactory->create()->load($userId)->getEmail();

        $html = parent::getFormHtml();
        $para = $this->setTemplate('Mopinion_FeedbackSurvey::edit_info.phtml')->toHtml();
        $footer = $this->setTemplate('Mopinion_FeedbackSurvey::edit_footer.phtml')->toHtml();
        //Inject the phtml file after the legend.
        $html = str_replace('</legend>', '</legend>' . $para, $html);
        return $html . $footer;
    }
}
