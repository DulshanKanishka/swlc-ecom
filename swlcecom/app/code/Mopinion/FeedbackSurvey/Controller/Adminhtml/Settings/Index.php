<?php
/**
 * The admin-specific functionality of the extension.
 *
 * @link       https://mopinion.com/author/kees-wolters/
 * @since      1.0.0
 * @author     Kees Wolters
 * @package    Mopinion_FeedbackSurvey
 */
namespace Mopinion\FeedbackSurvey\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\ObjectManager;
use Mopinion\FeedbackSurvey\Block\Adminhtml\Settings\Edit\FormContainer as EditFormContainer;
use Mopinion\FeedbackSurvey\Block\Adminhtml\Settings\Completed\FormContainer as CompletedFormContainer;
use Mopinion\FeedbackSurvey\Block\Adminhtml\Settings\Info as SettingsInfo;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mopinion_FeedbackSurvey::feedbacksurvey';

    /**
     * @param \Mopinion\FeedbackSurvey\Helper\Data $mopinionData
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mopinion\FeedbackSurvey\Helper\Data $mopinionData
    ) {
        $this->_mopinionData = $mopinionData;
        parent::__construct($context);
    }

    /**
     * Initialize action
     *
     * @return $this
     */
    protected function initAction()
    {

        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Mopinion_FeedbackSurvey::feedbacksurvey'
        )->_addBreadcrumb(
            __('Mopinion feedback survey settings'),
            __('Mopinion feedback survey settings')
        );
        return $this;
    }

    /**
     * @return void
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $orgId = $this->_mopinionData->getOrganizationId();
        if ($orgId) {
            $this->initAction()->_addContent(
                $this->_view->getLayout()->createBlock(
                    CompletedFormContainer::class
                )->setData(
                    'action',
                    $this->getUrl('mopinion/*/save')
                )
            );
        } else {
            $this->initAction()->_addContent(
                $this->_view->getLayout()->createBlock(
                    EditFormContainer::class
                )->setData(
                    'action',
                    $this->getUrl('mopinion/*/save')
                )
            );
        }

        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Mopinion'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            __('Mopinion feedback survey settings')
        );
        $this->_view->renderLayout();
    }
}
