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
namespace Mopinion\FeedbackSurvey\Block\Adminhtml\Settings\Edit\Edit;

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
        array $data = []
    ) {
        $this->_userFactory = $userFactory;
        $this->_authSession = $authSession;
        $this->systemStore = $systemStore;
        $this->_country = $country;
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
            ['legend' => __('Registration'), 'class' => 'fieldset-wide']
        );

        $countries = $this->_country->toOptionArray(false, 'US');
        $fieldset->addField(
            'country_id',
            'select',
            [
                'name' => 'country_id',
                'label' => __('Country'),
                'required' => true,
                'values' => $countries
            ]
        );

        $fieldset->addField(
            'email_disabled',
            'text',
            [
                'name' => 'email_disabled',
                'disabled' => true,
                'label' => __('Email'),
                'title' => __('Email'),
                'value' => $user->getEmail(),
                'required' => true
            ]
        );

        $fieldset->addField(
            'email',
            'hidden',
            [
                'name' => 'email',
                'value' => $user->getEmail(),
                'required' => true
            ]
        );

        $fieldset->addField(
            'password',
            'password',
            [
                'name' => 'password',
                'required' => true,
                'label' => __('Password'),
                'title' => __('Password'),
                'note' => __('Set your Mopinion account password'),
                'class' => 'validate-admin-password admin__control-text'
            ]
        );

        $fieldset->addField(
            'confirmation',
            'password',
            [
                'required' => true,
                'name' => 'password_confirmation',
                'label' => __('Password Confirmation'),
                'class' => 'validate-cpassword admin__control-text'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
