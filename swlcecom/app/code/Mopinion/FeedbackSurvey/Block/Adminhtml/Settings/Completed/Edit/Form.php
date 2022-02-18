<?php
/**
 * The admin-specific functionality of the extension.
 *
 * @link       https://mopinion.com/author/kees-wolters/
 * @since      1.0.0
 *
 * @author     Kees Wolters
 * @package    Mopinion_FeedbackSurvey
 */
namespace Mopinion\FeedbackSurvey\Block\Adminhtml\Settings\Completed\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $systemStore;

    /**
     * @var \Magento\CheckoutAgreements\Model\AgreementModeOptions
     */
    protected $agreementModeOptions;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Directory\Model\Config\Source\Country $country,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Mopinion\FeedbackSurvey\Helper\Data $mopinionData,
        array $data = []
    ) {
        $this->_userFactory = $userFactory;
        $this->_authSession = $authSession;
        $this->systemStore = $systemStore;
        $this->_country = $country;
        $this->_mopinionData = $mopinionData;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init class
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTitle(__('Mopinion feedback survey settings'));
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $userId = $this->_authSession->getUser()->getId();
        $user = $this->_userFactory->create()->load($userId);

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General'), 'class' => 'fieldset-wide']
        );

        $placement = $this->_mopinionData->getPosition();

        $fieldset->addField(
            'script_placement_via_mopinion',
            'radios',
            [
                'label' => __('Select how you want the Mopinion tag to be implemented'),
                'title' => __('Select how you want the Mopinion tag to be implemented'),
                'name' => 'script_placement',
                'required' => true,
                'note' => __('The Mopinion tag is injected directly into the Magento &lt;head&gt;-tag. '
                    .'No further action is required.'),
                'values' => [
                    [
                        'value'=> 'mopinioninfooter',
                        'label' => __('Append to footer (Recommended)'),
                    ]
                ],
                'value' => $placement
            ]
        );

        $fieldset->addField(
            'script_placement_external',
            'radios',
            [
                'label' =>  ' ',
                'title' => ' ',
                'name' => 'script_placement',
                'note' => __('With this option you need to paste the Mopinion tag (below) '.
                    'in your Tag Management solution of choice. Read more about that '.
                    '<a href="https://support.mopinion.com/help/how-do-i-install-mopinion-with-google-tag-manager">here</a>.'),
                'values' => [
                    [
                        'value'=> 'external',
                        'label'=> __('Use an external Tag Management solution')
                    ],
                ],
                'value' => $placement
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
