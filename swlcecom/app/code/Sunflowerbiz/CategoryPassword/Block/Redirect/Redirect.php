<?php

namespace Sunflowerbiz\CategoryPassword\Block\Redirect;

class Redirect extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Catalog\Model\Session
     */
    protected $customerSession;

    /**
        * Redirect constructor.
        *
        * @param \Magento\Framework\View\Element\Template\Context $context
        * @param array $data
        * @param \Magento\Sales\Model\OrderFactory $orderFactory
        * @param \Sunflowerbiz\Wechat\Helper\Data $sunHelper
        */
    public function __construct(
        \Magento\Framework\App\Response\Http $response,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = [],
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Locale\ResolverInterface $resolver
    ) {
        $this->response = $response;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);

        $this->_resolver = $resolver;
    }
    public function getPasswordHtml()
    {
        $customerSession=$this->customerSession;
        $session_passed_category = $customerSession->getData('passed_category');
        $category_id = $customerSession->getData('redirect_password_category_id');
        $category_url = $customerSession->getData('redirect_password_category');
        if ($category_id<=0) {
            return;
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $core_write = $resource->getConnection();
        $tableName = $resource->getTableName('category_password');
        $error_msg='';
        $category_password='';
        $password="";
        $selectsql= "select * from `" . $tableName . "` where category_id='" . $category_id . "'";
        $category_passwordfeach=$core_write->fetchAll($selectsql);
        if (count($category_passwordfeach)>0) {
            foreach ($category_passwordfeach as $categorypassword) {
                $category_password=$categorypassword['password'];
            }
        }

        $textview="Please enter password to view this category : ";
        $textwrong="<span style='color:#ff0000'>" . __("Sorry, your password is not correct.") . "</span>";
        $html="";
        $postData = $this->getRequest()->getPost();
        if (isset($postData['category_pass']) && $postData['category_pass']!=$category_password) {
            $error_msg=__($textwrong);
        }
        if (isset($postData['category_pass']) && $postData['category_pass']==$category_password) {
            $session_passed_category[]=$category_id;
            $customerSession->setData('passed_category', $session_passed_category);
            $html="<script>window.location.href='$category_url';</script>";
            $this->response->setRedirect($category_url);
        } else {
            $html= '<div style="text-align:center" class="categorypassword_block">' . __($textview) . '
													<form  method="post" id="category_password">
														<br><input type="password" name="category_pass" class="input-text" style="text-align:center">
														<br><br>
														<button type="submit" title="' . __('Submit') . '" onclick="this.submit()" class="button"><span><span>' . __('Submit') . '</span></span></button>
														<br><br>' . $error_msg . '
														</form>
														</div>';
        }
        return $html;
    }
}
