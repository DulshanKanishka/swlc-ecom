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

namespace Mopinion\FeedbackSurvey\Controller\Adminhtml\Settings;

use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\State\UserLockedException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends \Magento\Backend\App\Action
{
    /**
     * @param \Mopinion\FeedbackSurvey\Helper\Data $mopinionData
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mopinion\FeedbackSurvey\Helper\Data $mopinionData
    ) {
        $this->_mopinionData = $mopinionData;
        parent::__construct($context);
    }

    private function redirect()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath("*/*/");
    }

    /**
     * Saving information
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        if (strtolower($this->getRequest()->getMethod()) !== 'post') {
            return $this->redirect();
        }

        $placement = $this->getRequest()->getParam('script_placement', false);
        if ($placement) {
            if ($this->_mopinionData->getPosition() != $placement) {
                $this->_mopinionData->saveConfigData([
                    \Mopinion\FeedbackSurvey\Helper\Data::XML_PATH_POSITION => $placement
                ]);
            }

            $this->messageManager->addSuccess(
                __('Success!').
                '<br />'.
                __('The feedback survey was successfully updated. '.
                'Visit your public site to see the feedback survey in action!')
            );

            return $this->redirect();
        }

        $userId = $this->_objectManager->get(\Magento\Backend\Model\Auth\Session::class)->getUser()->getId();
        $country = strtolower($this->getRequest()->getParam('country_id', false));
        $email = strtolower($this->getRequest()->getParam('email', false));
        $password = (string)$this->getRequest()->getParam('password');
        $passwordConfirmation = (string)$this->getRequest()->getParam('password_confirmation');

        /** @var $user \Magento\User\Model\User */
        $user = $this->_objectManager->create(\Magento\User\Model\User::class)->load($userId);

        try {
            if (empty(trim($password)) || $password !== $passwordConfirmation) {
                throw new \Exception(__('Invalid password'));
            }

            if (empty($country)) {
                throw new \Exception(__("'Country' cannot be empty"));
            }

            $param = [
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'password' => $password,
                'email' => $email,
                'country' => $country,
                'siteName' => $this->_mopinionData->getStoreName()
            ];

            $this->_mopinionData->apiRequest($param);
            $this->messageManager->addSuccess(
                __('Success!').
                '<br />'.
                __('The feedback survey was successfully installed. '.
                'Visit your public site to see the feedback survey in action!')
            );
        } catch (\Exception $err) {
            if ($err->getMessage() !== 'UnexpectedError') {
                $this->messageManager->addErrorMessage($err->getMessage());
            } else {
                $this->messageManager->addErrorMessage(__('An error occurred while saving account.'));
            }
        }

        return $this->redirect();
    }
}
