<?php

namespace Magenest\AbandonedCart\Model;

use Magento\Framework\DB\Select;
use Magenest\AbandonedCart\Setup\InstallSchema;
use Magenest\AbandonedCart\Model\Config\Source\Mail;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Model\Product;
use Magento\Framework\Stdlib\DateTime;

/**
 * Class Cron
 * @package Magenest\AbandonedCart\Model
 */
class Cron
{
    const XML_PATH_BCC_NAME  = 'abandonedcart/general/bcc_name';
    const XML_PATH_BCC_EMAIL = 'abandonedcart/general/bcc_email';

    /** @var \Magenest\AbandonedCart\Model\AbandonedCartFactory $_abandonedCartFactory */
    protected $_abandonedCartFactory;

    /** @var \Magenest\AbandonedCart\Model\RuleFactory $_ruleFactory */
    protected $_ruleFactory;

    /** @var \Psr\Log\LoggerInterface $_logger */
    protected $_logger;

    /** @var \Magenest\AbandonedCart\Helper\MandrillConnector $_mandrillConnector */
    protected $_mandrillConnector;

    /** @var mixed|string */
    protected $_abandonedCartTime;

    /** @var \Magenest\AbandonedCart\Helper\Data $helper */
    protected $helper;

    /** @var \Magenest\AbandonedCart\Model\ResourceModel\AbandonedCart $_abandonedCartResource */
    protected $_abandonedCartResource;

    /** @var \Magenest\AbandonedCart\Model\LogContentFactory $_logContentFactory */
    protected $_logContentFactory;

    /** @var  \Magento\Quote\Model\QuoteFactory $_quoteFactory */
    protected $_quoteFactory;

    protected $matchingRules;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig */
    protected $_scopeConfig;

    /** @var \Magento\Framework\Encryption\Encryptor $_encryptor */
    protected $_encryptor;

    /** @var \Magento\Framework\Url $_urlBuilder */
    protected $_urlBuilder;

    /** @var \Magento\Email\Model\TemplateFactory $_emailTemplate */
    protected $_emailTemplate;

    protected $storeId = 0;

    protected $_vars   = [];

    /** @var \Magento\Framework\Stdlib\DateTime\Filter\Date $_dateFilter */
    protected $_dateFilter;

    /** @var \Magenest\AbandonedCart\Model\TestCampaignFactory $_testCampaignFactory */
    protected $_testCampaignFactory;

    /** @var  \Magenest\AbandonedCart\Helper\SendMail $_sendMailHelper */
    protected $_sendMailHelper;

    protected $_sendSmsHelper;

    /** @var \Magenest\AbandonedCart\Model\BlackListFactory $_blacklistFactory */
    protected $_blacklistFactory;

    protected $_unsubscribeFactory;

    protected $objectManager;

    /**
     * Cron constructor.
     *
     * @param AbandonedCartFactory $abandonedCartFactory
     * @param RuleFactory $ruleFactory
     * @param UnsubscribeFactory $unsubscribeFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magenest\AbandonedCart\Helper\MandrillConnector $mandrillConnector
     * @param \Magenest\AbandonedCart\Helper\Data $helperData
     * @param ResourceModel\AbandonedCart $abandonedCartResource
     * @param LogContentFactory $contentFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Framework\Url $url
     * @param \Magento\Email\Model\TemplateFactory $templateFactory
     * @param DateTime\Filter\Date $dateFilter
     * @param TestCampaignFactory $campaignFactory
     * @param \Magenest\AbandonedCart\Helper\SendMail $sendMail
     * @param \Magenest\AbandonedCart\Helper\SendSms $sendSms
     * @param BlackListFactory $blackListFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\AbandonedCartFactory $abandonedCartFactory,
        \Magenest\AbandonedCart\Model\RuleFactory $ruleFactory,
        \Magenest\AbandonedCart\Model\UnsubscribeFactory $unsubscribeFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magenest\AbandonedCart\Helper\MandrillConnector $mandrillConnector,
        \Magenest\AbandonedCart\Helper\Data $helperData,
        \Magenest\AbandonedCart\Model\ResourceModel\AbandonedCart $abandonedCartResource,
        \Magenest\AbandonedCart\Model\LogContentFactory $contentFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\Url $url,
        \Magento\Email\Model\TemplateFactory $templateFactory,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Magenest\AbandonedCart\Model\TestCampaignFactory $campaignFactory,
        \Magenest\AbandonedCart\Helper\SendMail $sendMail,
        \Magenest\AbandonedCart\Helper\SendSms $sendSms,
        \Magenest\AbandonedCart\Model\BlackListFactory $blackListFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_abandonedCartFactory  = $abandonedCartFactory;
        $this->_unsubscribeFactory    = $unsubscribeFactory;
        $this->_ruleFactory           = $ruleFactory;
        $this->_logger                = $logger;
        $this->_mandrillConnector     = $mandrillConnector;
        $this->helper                 = $helperData;
        $abandonedCartPeriod          = $this->helper->getAbandonedCartPeriod();
        $this->_abandonedCartTime     = $abandonedCartPeriod;
        $this->_abandonedCartResource = $abandonedCartResource;
        $this->_logContentFactory     = $contentFactory;
        $this->_quoteFactory          = $quoteFactory;
        $this->_scopeConfig           = $scopeConfig;
        $this->_encryptor             = $encryptor;
        $this->_urlBuilder            = $url;
        $this->_emailTemplate         = $templateFactory;
        $this->_dateFilter            = $dateFilter;
        $this->_testCampaignFactory   = $campaignFactory;
        $this->_sendMailHelper        = $sendMail;
        $this->_sendSmsHelper         = $sendSms;
        $this->_blacklistFactory      = $blackListFactory;
        $this->objectManager = $objectManager;
    }

    public function collectAbandonedCarts()
    {
        $enbaleModul = $this->helper->getConfig('abandonedcart/general/enable');
        if (!$enbaleModul) return;
        $oldId = $this->updateCartProcessedStatus();
        $this->collectCustomerAbandonedCart();
        $this->collectGuestAbandonedCart();

        //get all the abandoned cart
        $abandonedCarts = $this->_abandonedCartFactory->create()->getCollection()
            ->addFieldToFilter(
                'is_processed', [
                    ['eq' => 0], ['null' => true]
                ]
            )
            ->addFieldToFilter(
                'customer_email', ['notnull' => true]
            );
        if (!empty($oldId)) {
            $abandonedCarts->addFieldToFilter(
                'id', ['nin' => $oldId]
            );
        }
        foreach ($abandonedCarts as $abandonedCart) {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote               = $this->_quoteFactory->create()->loadByIdWithoutStore($abandonedCart->getQuoteId());
            $this->storeId       = $quote->getStoreId();
            $rules               = $this->getMatchingRule();
            $blackListCollection = $this->_blacklistFactory->create()->getCollection();
            if ($rules != null) {
                foreach ($rules as $rule) {
                    $customerEmail = $abandonedCart->getCustomerEmail();
                    $customerPhone = $abandonedCart->getCustomerPhone();
                    $isValidate    = $this->isValidate($rule, $quote);
                    if ($isValidate) {
                        if (!$this->checkInBlacklist($customerEmail, $blackListCollection) && !$this->checkInUnsubscribe($customerEmail)) {
                            $this->generateMail($quote, $rule, $abandonedCart, $customerEmail);
                        }
                        if (!$this->checkInBlacklist($customerPhone, $blackListCollection)) {
                            $this->generateSms($quote, $rule, $abandonedCart);
                        }
                    }
                }
            }
        }
        return $abandonedCarts;
    }

    public function isValidate($ruleModel, $quote)
    {
        /** @var \Magento\SalesRule\Model\Rule $saleRuleModel */
        $saleRuleModel = ObjectManager::getInstance()->create(\Magento\SalesRule\Model\Rule::class);
        $saleRuleModel->setData('conditions_serialized', $ruleModel->getData('conditions_serialized'));
        /** @var \Magento\Quote\Model\Quote $abandonedCart */
        $abandonedCart = $quote;
        $isValidate    = $this->validateCustomerGroupAndWebsite($ruleModel, $quote, $quote->getCustomerGroupId(), $quote->getStoreId());
        if (!$isValidate) {
            return $isValidate;
        }
        $isValidateFromTo = $this->validateApplyRule($ruleModel->getData('from_date'), $ruleModel->getData('to_date'));
        if (!$isValidateFromTo) {
            return $isValidateFromTo;
        }
        try {
            $saleRuleModel->getConditionsSerialized();
        } catch (\InvalidArgumentException $e) {
            $saleRuleModel->setData('conditions_serialized', json_encode(unserialize($ruleModel->getData('conditions_serialized'))));
        }
        $abandonedCart->setTotalsCollectedFlag(false);
        $abandonedCart->collectTotals();
        if ($abandonedCart->isVirtual()) {
            $address = $abandonedCart->getBillingAddress();
        } else {
            $address = $abandonedCart->getShippingAddress();
        }
        if (!$address->getTotalQty()) {
            $address->setTotalQty($quote->getItemsQty());
        }
        $isValidate = $saleRuleModel->validate($address);
        return $isValidate;
    }

    public function validateCustomerGroupAndWebsite($ruleModel, $quote, $customerGroupId = 0, $storesId = 0)
    {
        $isValidate = false;
        if (($ruleModel instanceof \Magenest\AbandonedCart\Model\Rule) && ($quote instanceof \Magento\Quote\Model\Quote)) {
            $isValidate = $ruleModel->isValidateTarget($customerGroupId, $storesId);
        }
        return $isValidate;
    }

    /**
     * @param $from
     * @param $to
     *
     * @return bool
     * @throws \Exception
     */
    public function validateApplyRule($from, $to)
    {
        $isValidate = false;
        $now        = new \DateTime();
        $today      = $now->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT);
        if ($from != "" && $to != "") {
            $fromDate = date(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT, strtotime($from));
            $toDate   = date(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT, strtotime($to));
            if ($today >= $fromDate && $today <= $toDate) {
                $isValidate = true;
            }
        }
        if ($from != "" && $to == "") {
            $fromDate = date(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT, strtotime($from));
            if ($today >= $fromDate) {
                $isValidate = true;
            }
        }
        if ($from == "" && $to != "") {
            $toDate = date(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT, strtotime($to));
            if ($today <= $toDate) {
                $isValidate = true;
            }
        }
        if ($from == "" && $to == "") {
            $isValidate = true;
        }
        return $isValidate;
    }

    public function checkInBlacklist($address, $collection)
    {
        /** @var \Magenest\AbandonedCart\Model\BlackList $blackListModel */
        $blackListModel = $collection->addFieldToFilter('address', $address)->getFirstItem();
        if ($blackListModel == null || !$blackListModel->getId()) {
            return false;
        } else {
            return true;
        }
    }

    public function checkInUnsubscribe($address)
    {
        $unsubscribes = $this->_unsubscribeFactory->create()->getCollection()
            ->addFieldToFilter('unsubscriber_email', $address)
            ->addFieldToFilter(
                'unsubscriber_status',
                \Magenest\AbandonedCart\Model\Config\Source\UnsubscriberStatus::UNSUBSCRIBED
            )->getFirstItem();
        if ($unsubscribes == null || !$unsubscribes->getId()) {
            return false;
        } else {
            return true;
        }
    }

    public function updateCartProcessedStatus()
    {
        $resource    = $this->_abandonedCartFactory->create()->getResource();
        $upperLimit  = $this->_abandonedCartResource->getUpperLimit($this->_abandonedCartTime);
        $connection  = $resource->getConnection();
        $quoteJoin   = $connection->select()
            ->joinInner(
                ['q' => $resource->getTable('quote')],
                'q.entity_id = abc.quote_id AND q.is_active = 1 AND abc.updated_at != q.updated_at'
            )
            ->reset(Select::COLUMNS)
            ->where('q.updated_at < ?', $upperLimit)
            ->columns(['updated_at', 'is_processed' => '0.0']);
        $updateQuery = $connection->updateFromSelect($quoteJoin, ['abc' => $resource->getTable(InstallSchema::ABANDONED_CART_TABLE)]);
        $connection->query($updateQuery);

        //get Abandoned Cart List Id
        $abandonedCartTable = $resource->getTable('magenest_abacar_list');
        $sql                = $resource->getConnection()->select()->from(
            ['m' => $abandonedCartTable],
            ['id']
        );
        $results            = $connection->fetchAll($sql);
        $ids                = [];
        foreach ($results as $result) {
            $ids[] = $result['id'];
        }
        return $ids;
    }

    public function collectCustomerAbandonedCart()
    {
        $abandonedCarts = $this->_abandonedCartResource->getAbandonedCartForInsertOperation($this->_abandonedCartTime);
        $records        = array();
        $count          = 0;
        $resource       = $this->_abandonedCartFactory->create()->getResource();
        foreach ($abandonedCarts as $quote) {

            if ($quote['customer_email'] == null && $quote['customer_id'] != null) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                /** @var \Magento\Customer\Model\Customer $customer */
                $customer                = $objectManager->create(\Magento\Customer\Model\Customer::class)->load($quote['customer_id']);
                $quote['customer_email'] = $customer->getEmail();
                if ($customer->getData('mobile_number')) {
                    $quote['mobile_number'] = $customer->getData('mobile_number');
                } else {
                    $quote['mobile_number'] = '';
                }
            }
            $records[] = [
                'quote_id'       => $quote['entity_id'],
                'customer_email' => $quote['customer_email'],
                'customer_phone' => $quote['mobile_number'],
                'type'           => 'Customer',
                'status'         => 0,
                'is_processed'   => 0
            ];
            $count++;
            if ($count > 5000) {
                $resource->getConnection()->insertMultiple($resource->getMainTable(), $records);
                $records = [];
                $count   = 0;
            }
        }
        if (count($records)) {
            $resource->getConnection()->insertMultiple($resource->getMainTable(), $records);
        }
        return $abandonedCarts;
    }

    public function collectGuestAbandonedCart()
    {
        $abandonedCarts = $this->_abandonedCartResource->getAbandonedCartOfGuest($this->_abandonedCartTime);
        $records        = [];
        $count          = 0;
        $resource       = $this->_abandonedCartFactory->create()->getResource();
        foreach ($abandonedCarts as $quote) {
            $records[] = [
                'quote_id'       => $quote['entity_id'],
                'customer_email' => $quote['email'],
                'type'           => 'Guest',
                'status'         => 0,
                'is_processed'   => 0
            ];
            $count++;
            if ($count > 5000) {
                $resource->getConnection()
                    ->insertMultiple($resource->getMainTable(), $records);
                $records = [];
                $count   = 0;
            }
        }
        if (count($records)) {
            $resource->getConnection()
                ->insertMultiple($resource->getMainTable(), $records);
        }
        return $abandonedCarts;
    }

    public function generateMail($quote, $rule, $abandonedCart, $customerEmail, $type = 'Email')
    {
        $bccName    = $this->_scopeConfig->getValue(self::XML_PATH_BCC_NAME);
        $bccEmail   = $this->_scopeConfig->getValue(self::XML_PATH_BCC_EMAIL);
        $mailChains = $rule->getEmailChain();
        $logModel   = $this->_logContentFactory->create();
        if ($mailChains) {
            $mailChains = json_decode($mailChains, true);
            foreach ($mailChains as $mail) {
                $mailData            = [];
                $logContentModel     = $logModel;
                $mailData['status']  = Mail::STATUS_QUEUED;
                $mailData['type']    = $type;
                $mailData['rule_id'] = $rule->getId();
                if ($quote->getData('customer_email')) {
                    $mailData['recipient_name']   = $quote->getData('customer_firstname') . ' ' . $quote->getData('customer_lastname');
                    $mailData['recipient_adress'] = $quote->getData('customer_email');
                } else {
                    $mailData['recipient_name']   = "Guest";
                    $mailData['recipient_adress'] = $abandonedCart->getData('customer_email');
                }

                $mailData['bcc_name']  = $bccName;
                $mailData['bcc_email'] = $bccEmail;
                $mailData['quote_id']  = $quote->getId();
                //handl schedule send email
                $schedule_time = 0;
                if ($mail['days']) {
                    $schedule_time += ($mail['days'] * 24 * 60 * 60);
                }
                if ($mail['hour']) {
                    $schedule_time += ($mail['hour'] * 60 * 60);
                }

                if ($mail['min']) {
                    $schedule_time += ($mail['min'] * 60);
                }
                $send_date             = '+' . $schedule_time . ' seconds';
                $mailData['send_date'] = $this->getSendDate($send_date);

                // create the mail content
                $emailTemplateId              = $mail['template'];
                $mailData['template_id']      = $emailTemplateId;
                $mailData['abandonedcart_id'] = $abandonedCart->getId();
                $mailData['clicks']  = '0';
                $mailData['opened']  = '0';
                $logContentModel->setData($mailData)->save();

                $this->setUnsubscribeVars($customerEmail, $rule);
                $emailTemplateModel = $this->getTemplateInstance()->load($emailTemplateId);

                $this->prepareMail($quote, $customerEmail, $logContentModel->getId(), $emailTemplateModel);

                $emailTemplateModel->setTemplateText($this->insertCoupon(
                    $rule, $mail, $emailTemplateModel->getTemplateText(), $logContentModel
                ));
                $emailTemplateModel->setVars(array_merge($this->_vars, [
                    'store' => ObjectManager::getInstance()->get(\Magento\Store\Model\StoreManagerInterface::class)->getStore($this->storeId)
                ]));
                $emailTemplateModel->setDesignConfig([
                    'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->storeId
                ]);
                if ($emailTemplateModel->getSubject()) {
                    $mailData['subject'] = $emailTemplateModel->getSubject();
                }
                //$this->addTrackingCode($logContentModel);
                //add checking opend email
                $this->addTrackingCode($logContentModel);
                $mailData['content']           = html_entity_decode($emailTemplateModel->getProcessedTemplate($this->_vars));
                $mailData['preview_content']   = $mailData['content'];
                $mailData['styles']            = $emailTemplateModel->getTemplateStyles();
                $mailData['context_vars']      = json_encode($this->_vars);
                $mailData['cancel_serialized'] = $rule->getData('cancel_serialized');
                $attachedFiles                 = $rule->getData('attached_files');
                $mailData['attachments']       = $attachedFiles;
                $logContentModel->addData($mailData)->save();

                //add tracking code for email content

                $content = $this->applyClickTracking($this->applyGoogleAnalytics($logContentModel, $rule), $logContentModel->getId());
                $logContentModel->setContent($content)->save();
                if ($abandonedCart instanceof \Magenest\AbandonedCart\Model\AbandonedCart) {
                    $abandonedCart->addData(['is_processed' => 1])->save();
                }
            }
        }
    }

    public function prepareMail($quote, $customerEmail, $logId, $emailTemplateModel)
    {
        /** @var \Magento\Quote\Model\Quote $emailTarget */
        $emailTarget    = $quote;
        $quote_id       = $emailTarget->getEntityId();
        $customer_id    = $emailTarget->getCustomerId() != null ? $emailTarget->getCustomerId() : '';
        $customer_email = $customerEmail;

        //Customer Auto login
        $key          = $customer_id . $customer_email;
        $autoLoginKey = self::base64UrlEncode($this->_encryptor->encrypt($key));

        $resumeLinkWithSecurityKey = $this->_urlBuilder->getUrl(
            'abandonedcart/track/restore',
            ['_current' => false, 'utc' => $quote_id, 'u' => $autoLoginKey, 'l' => $logId]
        );
        $pattern                   = '/\/\?SID.*/';
        $resumeLink                = preg_replace($pattern, '', $resumeLinkWithSecurityKey);
        // get the cart html to render in email reminder
        $items              = $emailTarget->getAllItems();
        $itemsHtml          = "<table style='border-collapse: collapse;width: 100%;'>
                        <tr style='border: 1px solid #ddd;padding: 8px;'>
                            <th style='border: 1px solid #ddd;padding: 8px; padding-top: 12px;padding-bottom: 12px;text-align: left;'>" . __('Image') . "</th>
                            <th style='border: 1px solid #ddd;padding: 8px; padding-top: 12px;padding-bottom: 12px;text-align: left;'>" . __('Name') . "</th>
                            <th style='border: 1px solid #ddd;padding: 8px; padding-top: 12px;padding-bottom: 12px;text-align: left;'>" . __('Qty') . "</th>
                            <th style='border: 1px solid #ddd;padding: 8px; padding-top: 12px;padding-bottom: 12px;text-align: left;'>" . __('Price') . "</th>
                        </tr>";
        $relatedProductHtml = "";
        foreach ($items as $item) {
            $itemsHtml          .= $this->getItemHtml($item);
            $relatedProductHtml .= $this->getRelatedProductsGridHtml($item->getProduct(), $quote);
        }
        $itemsHtml .= "</table>";
        // get the customer of the abandoned cart
        if ($emailTarget->getCustomerFirstname()) {
            $customerFirstName = $emailTarget->getCustomerFirstname();
            $customerLastName  = $emailTarget->getCustomerLastname();
            $customerName      = $customerFirstName . ' ' . $customerLastName;
        } else {
            $customerName = 'Guest';
        }
        $this->setVars(array_merge($this->_vars, [
            'cart_items'   => $itemsHtml,
            'resumeLink'   => $resumeLink,
            'customerName' => $customerName
        ]));
    }

    public function setUnsubscribeVars($email, $ruleModel)
    {
        $ecryptedEmail       = self::base64UrlEncode($this->_encryptor->encrypt($email));
        $encryptedRuleId     = self::base64UrlEncode($this->_encryptor->encrypt($ruleModel->getId()));
        $unsubscribeLink     = $this->_urlBuilder->getUrl(
            'abandonedcart/unsubscribe/unsubscribe',
            ['_current' => false, 'e' => $ecryptedEmail]
        );
        $unsubscribeRuleLink = $this->_urlBuilder->getUrl(
            'abandonedcart/unsubscribe/unsubscribe',
            ['_current' => false, 'e' => $ecryptedEmail, 'r' => $encryptedRuleId]
        );
        $resubscribeLink     = $this->_urlBuilder->getUrl(
            'abandonedcart/unsubscribe/resubscribe',
            ['_current' => false, 'e' => $ecryptedEmail]
        );
        $resubscribeRuleLink = $this->_urlBuilder->getUrl(
            'abandonedcart/unsubscribe/resubscribe',
            ['_current' => false, 'e' => $ecryptedEmail, 'r' => $encryptedRuleId]
        );
        $this->setVars(array_merge($this->_vars, [
            'unsubscribeLink'     => $unsubscribeLink,
            'unsubscribeRuleLink' => $unsubscribeRuleLink,
            'resubscribeLink'     => $resubscribeLink,
            'resubscribeRuleLink' => $resubscribeRuleLink
        ]));
    }

    public function getItemHtml(\Magento\Quote\Model\Quote\Item $item)
    {
        if ($item->getParentItemId()) {
            return '';
        }
        $currency     = $item->getQuote()->getCurrency();
        $currencyCode = $currency ? $currency->getQuoteCurrencyCode() : '';
        $productId    = $item->getProduct() ? $item->getProduct()->getId() : null;
        /** @var \Magento\Catalog\Model\Product $product */
        $product         = ObjectManager::getInstance()->create(Product::class)->load($productId);
        $productImageUrl = $product->getMediaGalleryImages() ? $product->getMediaGalleryImages()->getFirstItem()->getUrl() : null;
        if ($children = $item->getChildren()) {
            if ($childItem = reset($children)) {
                $childProduct    = ObjectManager::getInstance()->create(Product::class)->load($childItem->getProductId());
                $productImageUrl = $childProduct->getMediaGalleryImages() ? $childProduct->getMediaGalleryImages()->getFirstItem()->getUrl() : null;
            }
        }
        $html = "<tr style='border: 1px solid #ddd;padding: 8px;'>
                    <td style='border: 1px solid #ddd;padding: 8px;'>
                        <a href='" . $product->getProductUrl() . "' target='_blank'>
                            <img src='" . $productImageUrl . "' height='150' width='120'>
                        </a>
                    </td>
                    <td style='border: 1px solid #ddd;padding: 8px;'>
                        <span class='product-name'>" . $product->getName() . "</span>
                    </td>
                    <td style='border: 1px solid #ddd;padding: 8px;'>
                        <p style='font-weight: bold'>" . number_format($item->getQty(), 2) . "</p>
                    </td>
                    <td style='border: 1px solid #ddd;padding: 8px;'>
                        <p style='font-weight: bold'>" . number_format($item->getPrice(), 2) . ' ' . $currencyCode . "</p>
                    </td>
                </tr>";
        return $html;
    }

    protected function getRelatedProductsGridHtml($product, $quote)
    {
        if (!$product->getId()) {
            return '';
        }
        $relatedProductHtml   = "<table style='border-collapse: collapse;width: 100%;'>
                                    <caption>" . __('Related Products') . "</caption>
                                    <tr>
                                        <td>" . __('Image') . "</td>
                                        <td>" . __('Name') . "</td>
                                        <td>" . __('Price') . "</td>
                                    </tr>";
        $crossSellProductHtml = "<table style='border-collapse: collapse;width: 100%;'>
                                    <caption>" . __('Cross-Sell Products') . "</caption>
                                    <tr>
                                        <td>" . __('Image') . "</td>
                                        <td>" . __('Name') . "</td>
                                        <td>" . __('Price') . "</td>
                                    </tr>";
        $upSellProductHtml    = "<table style='border-collapse: collapse;width: 100%;'>
                                    <caption>" . __('Up-Sell Products') . "</caption>
                                    <tr>
                                        <td>" . __('Image') . "</td>
                                        <td>" . __('Name') . "</td>
                                        <td>" . __('Price') . "</td>
                                    </tr>";
        $currency             = $quote->getCurrency();
        $currencyCode         = $currency ? $currency->getQuoteCurrencyCode() : '';
        foreach ($product->getRelatedProductIds() as $relatedProductId) {
            $relatedProductHtml .= $this->getProductHtml($relatedProductId, $currencyCode);
        }
        foreach ($product->getUpSellProductIds() as $relatedProductId) {
            $upSellProductHtml .= $this->getProductHtml($relatedProductId, $currencyCode);
        }
        foreach ($product->getCrossSellProductIds() as $relatedProductId) {
            $crossSellProductHtml .= $this->getProductHtml($relatedProductId, $currencyCode);
        }

        $relatedProductHtml   .= "</table>";
        $upSellProductHtml    .= "</table>";
        $crossSellProductHtml .= "</table>";
        $this->setVars(array_merge($this->_vars, [
            'related_products'    => $relatedProductHtml,
            'cross_sell_products' => $crossSellProductHtml,
            'up_sell_products'    => $upSellProductHtml
        ]));
    }

    /**
     * @param int $productId
     *
     * @return string
     */
    protected function getProductHtml($productId, $currencyCode)
    {
        /** @var Product $product */
        $product = \Magento\Framework\App\ObjectManager::getInstance()->create(\Magento\Catalog\Model\Product::class)->load($productId);
        if (!$product->getId()) {
            return '';
        }
        $html = "<tr>
                    <td>
                        <a href='" . $product->getProductUrl() . "' target='_blank'>
                            <img src='" . $product->getMediaGalleryImages()->getFirstItem()->getUrl() . "' height='150' width='120'>
                        </a>
                    </td>
                    <td class='product-name-container'>
                        <span class='product-name'>" . $product->getName() . "</span>
                    </td>
                    <td class='product-price-container'>
                        <p style='font-weight: bold'>" . $currencyCode . number_format($product->getFinalPrice(), 2) . "</p>
                    </td>
                </tr>";
        return $html;
    }

    public static function base64UrlEncode($input)
    {
        return strtr(base64_encode($input), '+/=', '._-');
    }

    public static function base64UrlDecode($input)
    {
        return base64_decode(strtr($input, '._-', '+/='));
    }

    /**
     * get the active rule for a type
     * @return \Magenest\AbandonedCart\Model\ResourceModel\Rule\Collection
     */
    public function getMatchingRule()
    {
        $matchingRules = [];
        /** @var $collection  \Magenest\AbandonedCart\Model\ResourceModel\Rule\Collection */
        $collection = $this->_ruleFactory->create()->getCollection()->addFieldToFilter('status', '1')->setOrder('priority', 'ASC');
        foreach ($collection as $ruleModel) {
            $matchingRules[] = $ruleModel;
            if ($ruleModel->getDiscardSubsequent() == "1" || $ruleModel->getDiscardSubsequent() == 1) {
                break;
            }
        }
        return $matchingRules;
    }

    public function sendScheduledMail()
    {
        $this->scheduleCancelConditionRule();
        $mandrillEnable      = $this->_mandrillConnector->isEnable();
        $mailCollections     = $this->_logContentFactory->create()->getCollection()->getMailsNeedToBeSent();
        $blackListCollection = $this->_blacklistFactory->create()->getCollection();
        if ($mailCollections != null) {
            if ($mandrillEnable) {
                $this->_mandrillConnector->sendEmails($mailCollections, $blackListCollection);
            } else {
                foreach ($mailCollections as $mailCollection) {
                    if (!$this->checkInBlacklist($mailCollection->getRecipientAdress(), $blackListCollection)) {
                        $this->_sendMailHelper->send($mailCollection);
                    }
                }
            }
        }
    }

    public function scheduleSendSMS()
    {
        $this->scheduleCancelConditionRule();
        $smsCollections = $this->_logContentFactory->create()->getCollection()->getSMSNeedToBeSent();
        if ($smsCollections != null) {
            foreach ($smsCollections as $smsCollection) {
                $respones = $this->_sendSmsHelper->send($smsCollection);
                $this->saveSendSMSLog($smsCollection, $respones);
            }
        }
    }

    public function saveSendSMSLog($smsCollection, $respones)
    {
        if ($respones['messages'][0]['status'] == "0") {
            $smsCollection->addData(['status' => Mail::STATUS_SENT, 'log' => 'Ok']);
        } else {
            $smsCollection->addData(['status' => Mail::STATUS_FAILED, 'log' => 'FAILED']);
        }
        $smsCollection->save();
    }

    public function getTemplateContent($configPath, $var)
    {
        $template = $this->getTemplateInstance();
        try {
            $content = $template->getTemplateContent($configPath, $var);
        } catch (\Exception $ex) {
            $content = '(ERROR: ' . $ex->getMessage() . ')';
        }
        return $content;
    }

    public function getTemplateInstance()
    {
        /** @var \Magento\Email\Model\Template $template */
        $template = $this->_emailTemplate->create();
        $template->setDesignConfig([
            'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => $this->storeId
        ]);
        return $template;
    }

    /**
     * @return mixed
     */
    public function getVars()
    {
        return $this->_vars;
    }

    /**
     * @param $vars
     *
     * @return $this
     */
    public function setVars($vars)
    {
        $this->_vars = $vars;
        return $this;
    }

    public function getSendDate($modify)
    {
        $current_date_time = new \DateTime();
        $current_date_time->modify($modify);
        $current_date_time->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        return $current_date_time;
    }

    public function insertCoupon($rule, $mail, $emailContent, $logContentModel)
    {
        if (!$rule->getId()) {
            return;
        }
        if ($mail['enable_coupon'] == 0) {
            return $emailContent;
        }
        try {
            $pattern = '/\{\{var\s+coupon.code\}\}/';
            preg_match_all($pattern, $emailContent, $matches, PREG_SET_ORDER);
            if (count($matches) >= 1) {
                if (isset($mail['is_use_cart_rule']) && $mail['is_use_cart_rule'] == 1) {
                    $massgenerator = \Magento\Framework\App\ObjectManager::getInstance()->create('Magenest\AbandonedCart\Model\Coupon\Massgenerator');
                    $massgenerator->setFormat('alphanum');
                    $massgenerator->setLength(10);
                    $expiredTime = [];
                    $mailId      = $mail['id'];
                    $sendDate    = $logContentModel->getSendDate();
                    $codes       = $massgenerator->generateAbandonedCartPool($mail['promotion_rule'], count($matches), $mailId, $expiredTime, $sendDate);
                    foreach ($matches as $key => $match) {
                        $patterns[0]     = '/' . $match[0] . '/';
                        $replacements[0] = $codes[$key];
                        $emailContent    = preg_replace($patterns, $replacements, $emailContent, 1);
                    }
                    $logContentModel->setCouponCode(json_encode($codes))->save();
                } else {
                    /** @var $model \Magento\SalesRule\Model\Rule */
                    $salesRuleModel = ObjectManager::getInstance()->create(\Magento\SalesRule\Model\Rule::class);
                    if ($mail['expired_in'] != "") {
                        $expired = $mail['expired_in'] * 24 * 60;
                    } else {
                        $expired = null;
                    }
                    if ($expired != null && isset($mail['days']) && $mail['days'] != "") {
                        $expired += ($mail['days'] * 24 * 60);
                    }
                    if ($expired != null && isset($mail['hour']) && $mail['hour'] != "") {
                        $expired += ($mail['hour'] * 24);
                    }
                    if ($expired != null && isset($mail['min']) && $mail['min'] != "") {
                        $expired += $mail['min'];
                    }

                    $from_date = '';
                    if ($expired == null) {
                        $to_date = null;
                    } else {
                        $to_date = $this->helper->formateDate($expired);
                    }
                    $use_coupon = $this->helper->getConfig('abandonedcart/setting/considered_coupon');
                    $codes      = [];
                    foreach ($matches as $key => $match) {
                        $data         = [];
                        $coupon_code  = $this->generateCode();
                        $codes[]      = $coupon_code;
                        $patterns[0]  = '/' . $match[0] . '/';
                        $emailContent = preg_replace($patterns, $coupon_code, $emailContent, 1);
                        $data         = [
                            'name'                  => $rule->getName() . ': ' . $logContentModel->getData('recipient_adress'),
                            'uses_per_coupon'       => 1,
                            'uses_per_customer'     => 0,
                            'sort_order'            => "0",
                            'discount_amount'       => $mail['discount_amount'],
                            'discount_qty'          => $mail['max_qty_discount'],
                            'discount_step'         => $mail['discount_qty_step'],
                            'store_labels'          => [
                                0 => "Abandoned Cart Discount Code",
                                1 => ""
                            ],
                            'is_active'             => "1",
                            'use_auto_generation'   => "0",
                            'is_rss'                => "1",
                            'stop_rules_processing' => "0",
                            'apply_to_shipping'     => "0",
                            'description'           => "Generated By Magenest AbandonedCart.",
                            'from_date'             => $from_date,
                            'to_date'               => $to_date,
                            'simple_action'         => $mail['coupon_type'],
                            'simple_free_shipping'  => "",
                            'coupon_type'           => 2,
                            'coupon_code'           => $coupon_code,
                            'website_ids'           => [
                                0 => '1'
                            ],
                            'customer_group_ids'    => [
                                0 => "0",
                                1 => "1",
                                2 => "2",
                                3 => "3"
                            ],
                            'rule'                  => json_decode($rule->getData('conditions_serialized'), true)
                        ];

                        $filterValues = ['from_date' => $this->_dateFilter];
                        if ($to_date) {
                            $filterValues['to_date'] = $this->_dateFilter;
                        }
                        $inputFilter    = new \Zend_Filter_Input(
                            $filterValues,
                            [],
                            $data
                        );
                        $data           = $inputFilter->getUnescaped();
                        $validateResult = $salesRuleModel->validateData(new \Magento\Framework\DataObject($data));
                        if ($validateResult !== true) {
                            foreach ($validateResult as $errorMessage) {
                                throw new \Exception($errorMessage);
                            }
                        }
                        if (isset($data['simple_action'])
                            &&
                            $data['simple_action'] == 'by_percent'
                            &&
                            isset($data['discount_amount'])
                        ) {
                            $data['discount_amount'] = min(100, $data['discount_amount']);
                        }
                        if (isset($data['rule']['conditions'])) {
                            $data['conditions'] = $data['rule']['conditions'];
                        }
                        if (isset($data['rule']['actions'])) {
                            $data['actions'] = $data['rule']['actions'];
                        }
                        unset($data['rule']);
                        $salesRuleModel->loadPost($data);
                        $salesRuleModel->save();

                    }
                    $logContentModel->setCouponCode(json_encode($codes))->save();
                }
            }

        } catch (\Exception $e) {
            ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->critical($e);
        }

        return $emailContent;
    }

    public function generateCode()
    {
        /** @var \Magento\SalesRule\Model\Coupon\Massgenerator $massgenerator */
        $massgenerator = ObjectManager::getInstance()->create(\Magento\SalesRule\Model\Coupon\Massgenerator::class);
        $massgenerator->setLength(10);
        $coupon_code = $massgenerator->generateCode();
        return $coupon_code;
    }

    public function generateSms($quote, $rule, $abandonedCart)
    {
        $messageChain = json_decode($rule->getSmsChain(), true);
        if (is_array($messageChain) && !empty($messageChain)) {
            foreach ($messageChain as $message) {
                $smsData            = [];
                $logContentModel    = $this->_logContentFactory->create();
                $smsData['status']  = Mail::STATUS_QUEUED;
                $smsData['type']    = 'SMS';
                $smsData['rule_id'] = $rule->getId();
                if ($quote->getData('customer_email')) {
                    $smsData['recipient_name']   = $quote->getData('customer_firstname') . ' ' . $quote->getData('customer_lastname');
                    $smsData['recipient_adress'] = $abandonedCart->getData('customer_phone');
                } else {
                    $smsData['recipient_name']   = "Guest";
                    $smsData['recipient_adress'] = $abandonedCart->getData('customer_phone');
                }
                if ($abandonedCart->getData('customer_phone') == '' || $abandonedCart->getData('customer_phone') == null) {
                    continue;
                }
                //handl schedule send email
                $schedule_time = 0;
                if ($message['days']) {
                    $schedule_time += ($message['days'] * 24 * 60 * 60);
                }
                if ($message['hour']) {
                    $schedule_time += ($message['hour'] * 60 * 60);
                }

                if ($message['min']) {
                    $schedule_time += ($message['min'] * 60);
                }
                $send_date            = '+' . $schedule_time . ' seconds';
                $smsData['send_date'] = $this->getSendDate($send_date);
//                $this->prepareSms($quote);

                $smsData['abandonedcart_id'] = $abandonedCart->getId();
                $logContentModel->setData($smsData)->save();
                $smsData['content']           = $this->insertCoupon($rule, $message, $message['content'], $logContentModel);
                $smsData['content']           = html_entity_decode($smsData['content']);
                $smsData['preview_content']   = $smsData['content'];
                $smsData['styles']            = '';
                $smsData['context_vars']      = json_encode($this->_vars);
                $smsData['cancel_serialized'] = $rule->getData('cancel_serialized');
                $smsData['attachments']       = '';
                $logContentModel->addData($smsData)->save();
                if ($abandonedCart instanceof \Magenest\AbandonedCart\Model\AbandonedCart) {
                    $abandonedCart->addData(['is_processed' => 1])->save();
                }
            }
        }

    }

    public function collectAbandonedCartsForTestCampaign($rule_id)
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $tableName     = 'magenest_abacar_testcampaign';
            $guest         = $this->_abandonedCartResource->getAbandonedCartOfGuestForTestCampaign($this->_abandonedCartTime, $tableName);
            $member        = $this->_abandonedCartResource->getAbandonedCartOfMemberForTestCampaign($this->_abandonedCartTime, $tableName);
            $testCampaign  = $this->_abandonedCartResource->getAllTestCampaign($rule_id);
            // for guest
            $records  = array();
            $count    = 0;
            $resource = $this->_testCampaignFactory->create()->getResource();
            foreach ($guest as $record) {
                if (in_array($record['entity_id'], $testCampaign)) {
                    continue;
                }
                $records[] = [
                    'quote_id'       => $record['entity_id'],
                    'customer_email' => $record['email'],
                    'rule_id'        => $rule_id,
                    'cart_subtotal'  => $record['subtotal'],
                    'is_send'        => 0
                ];
                $count++;
                if ($count > 5000) {
                    $resource->getConnection()
                        ->insertMultiple($resource->getMainTable(), $records);
                    $records = [];
                    $count   = 0;
                }
            }
            if (count($records)) {
                $resource->getConnection()
                    ->insertMultiple($resource->getMainTable(), $records);
            }
            // for member
            $records = array();
            $count   = 0;
            foreach ($member as $record) {
                if (in_array($record['entity_id'], $testCampaign)) {
                    continue;
                }
                if ($record['customer_email'] == null && $record['customer_id'] != null) {
                    /** @var \Magento\Customer\Model\Customer $customer */
                    $customer                 = $objectManager->create(\Magento\Customer\Model\Customer::class)->load($record['customer_id']);
                    $record['customer_email'] = $customer->getEmail();
                    if ($customer->getData('mobile_number')) {
                        $record['mobile_number'] = $customer->getData('mobile_number');
                    } else {
                        $record['mobile_number'] = '';
                    }
                }
                $records[] = [
                    'quote_id'       => $record['entity_id'],
                    'customer_email' => $record['customer_email'],
                    'rule_id'        => $rule_id,
                    'cart_subtotal'  => $record['subtotal'],
                    'is_send'        => 0
                ];
                $count++;
                if ($count > 5000) {
                    $resource->getConnection()
                        ->insertMultiple($resource->getMainTable(), $records);
                    $records = [];
                    $count   = 0;
                }
            }
            if (count($records)) {
                $resource->getConnection()
                    ->insertMultiple($resource->getMainTable(), $records);
            }
            //Generated email content
            /** @var \Magenest\AbandonedCart\Model\Rule $ruleModel */
            $ruleModel     = $this->_ruleFactory->create()->load($rule_id);
            $testCampaigns = $this->_testCampaignFactory->create()->getCollection()
                ->addFieldToFilter('is_send', ['eq' => 0])
                ->addFieldToFilter('customer_email', ['notnull' => true])
                ->addFieldToFilter('rule_id', $rule_id);
            /** @var \Magento\Quote\Model\Quote $abandoned_cart */
            foreach ($testCampaigns->getItems() as $testCampaign) {
                $customerEmail = $testCampaign->getData('customer_email');
                $quote_id      = $testCampaign->getQuoteId();
                $quoteModel    = $this->_quoteFactory->create()->load($quote_id);
                $type          = 'Campaign';
                $this->generateMail($quoteModel, $ruleModel, $testCampaign, $customerEmail, $type);
                $testCampaign->setIsSend(1)->save();
            }
        } catch (\Exception $exception) {
            $this->_logger->critical($exception->getMessage());
        }
    }

    public function addTrackingCode($logContentModel)
    {
        $trackingContent = self::getTrackingCode($logContentModel->getId());
        $this->setVars(array_merge($this->_vars, [
            'checkOpened' => $trackingContent
        ]));
    }

    public static function getTrackingCode($mailId)
    {
        /** @var \Magento\Framework\App\ObjectManager $objectManager */
        $objectManager   = \Magento\Framework\App\ObjectManager::getInstance();
        $encryptor       = $objectManager->create('\Magento\Framework\Encryption\Encryptor');
        $urlBuilder      = $objectManager->get(\Magento\Framework\Url::class);
        $trackingId      = self::base64UrlEncode($encryptor->encrypt($mailId));
        $trackingUrl     = $urlBuilder->getUrl('abandonedcart/track/email', ['id' => $trackingId]);
        $trackingContent = "<img src='{$trackingUrl}' width='0' height='0' />";
        return $trackingContent;
    }

    public function applyGoogleAnalytics($logContentModel, $rule)
    {
        $content    = $logContentModel->getContent();
        $gaMedium   = $rule->getData('ga_medium');
        $gaCampaign = $rule->getData('ga_campaign');
        $gaContent  = $rule->getData('ga_content');
        $gaTerm     = $rule->getData('ga_term');
        $gaSource   = $rule->getData('ga_source');
        if ($gaMedium || $gaCampaign || $gaContent || $gaTerm || $gaSource) {
            $analytics = '?utm_medium=' . $gaMedium . '&utm_campaign=' . $gaCampaign . '&utm_source=' . $gaSource;

            if ($gaContent) {
                $analytics .= '&utm_content=' . $gaContent;
            }
            if ($gaTerm) {
                $analytics .= '&utm_term=' . $gaTerm . '&uq=';
            }
            $this->googleAnalytic = $analytics;
            $pattern              = "/\b(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[-A-Z0-9+&@#\/%=~_|$?!:,.]*[A-Z0-9+&@#\/%=~_|$]/i";
            $content              = preg_replace_callback($pattern, [$this, 'replaceUrlWithGA'], $content);
        }
        return $content;
    }

    public function replaceUrlWithGA($input)
    {
        if (isset($input[0])) {
            if (!preg_match('/\.(ico|jpg|jpeg|png|gif|svg|js|css|swf|eot|ttf|otf|woff|woff2)($|\?)/i', $input[0])) {
                return $input[0] .= $this->googleAnalytic;
            }
            return $input[0];
        } else {
            return $input;
        }
    }

    public static function applyClickTracking($input, $mailId)
    {
        $pattern = "/\b(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[-A-Z0-9+&@#\/%=~_|$?!:,.]*[A-Z0-9+&@#\/%=~_|$]/i";
        $input   = preg_replace_callback($pattern, function ($matches) use ($mailId) {
            if (isset($matches[0])) {
                if (!preg_match('/\.(ico|jpg|jpeg|png|gif|svg|js|css|swf|eot|ttf|otf|woff|woff2)($|\?)/i', $matches[0])) {
                    if (strpos($matches[0], 'track/email') === false) {
                        $objectManager  = \Magento\Framework\App\ObjectManager::getInstance();
                        $destination    = self::base64UrlEncode($matches[0]);
                        $encryptor      = $objectManager->get(\Magento\Framework\Encryption\Encryptor::class);
                        $url            = $objectManager->get(\Magento\Framework\Url::class);
                        $ecryptedMailId = self::base64UrlEncode($encryptor->encrypt($mailId));
                        $trackingUrl    = $url->getUrl('abandonedcart/track/click', [
                            'des' => $destination,
                            'id'  => $ecryptedMailId
                        ]);
                        return $trackingUrl;
                    }
                }
                return $matches[0];
            } else {
                return $matches;
            }
        }, $input);
        return $input;
    }

    public function scheduleCancelConditionRule()
    {
        $ruleCollections = $this->_ruleFactory->create()->getCollection()
            ->addFieldToFilter(
                'status', 1
            )->addFieldToFilter(
                'cancel_rule_when', ['notnull' => true]
            );
        $arr             = [1, 2];
        $ids             = [];
        foreach ($ruleCollections as $rule) {
            $cancel_rule_when = json_decode($rule->getCancelRuleWhen(), true);
            if (in_array(1, $cancel_rule_when)) {
                $ids[$arr[0]][] = $rule->getId();
            }
            if (in_array(2, $cancel_rule_when)) {
                $ids[$arr[1]][] = $rule->getId();
            }
        }
        if (!empty($ids)) {
            if (isset($ids[1])) {
                $this->cancelRuleWithAnyProductOutStock($ids[1]);
            }
            if (isset($ids[2])) {
                $this->cancelRuleWithAllProductOutStock($ids[2]);
            }
        }
    }

    /**
     * @param $ruleIds
     */
    public function cancelRuleWithAnyProductOutStock($ruleIds)
    {
        $logContentModels    = $this->_logContentFactory->create()->getCollection()
            ->addFieldToFilter('main_table.status', Mail::STATUS_QUEUED)
            ->addFieldToFilter('main_table.rule_id', ['IN' => $ruleIds]);
        $sortAbandonedCartId = [];
//        $abandonedCartCollection = $this->_abandonedCartFactory->create()->getCollection();
        if ($logContentModels != null) {
            foreach ($logContentModels as $logContent) {
                $sortAbandonedCartId[$logContent->getData('abandonedcart_id')][] = $logContent;
            }
            if (!empty($sortAbandonedCartId)) {
                foreach ($sortAbandonedCartId as $key => $logContents) {
                    if (is_array($logContents)) {
                        foreach ($logContents as $logContent) {
                            $quote_id = $logContent->getData('quote_id');
                            $allItems = $this->_quoteFactory->create()->getCollection()->addFieldToFilter('entity_id', $quote_id)->getFirstItem()->getAllItems();
                            /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
                            $productRepository = $this->objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface');
                            /** @var \Magento\Quote\Model\Quote\Item $item */
                            foreach ($allItems as $item) {
                                /** @var \Magento\Catalog\Model\Product $product */
                                $product     = $item->getProduct();
                                /** Prepare stock items */
                                $firstStockItem = $productRepository->getById($product->getId())->getExtensionAttributes()->getStockItem();
                                $is_in_stock = $firstStockItem->getData('is_in_stock');
                                if ($is_in_stock != "" && $is_in_stock < 1) {
                                    $logContent->addData([
                                        'status' => Mail::STATUS_CANCELLED,
                                        'log'    => 'Any product went out of stock'
                                    ]);
                                    $logContent->save();
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

    }

    public function cancelRuleWithAllProductOutStock($ruleIds)
    {
        $logContentModels        = $this->_logContentFactory->create()->getCollection()
            ->addFieldToFilter('status', Mail::STATUS_QUEUED)
            ->addFieldToFilter('rule_id', ['IN' => $ruleIds]);
        $sortAbandonedCartId     = [];
        $abandonedCartCollection = $this->_abandonedCartFactory->create()->getCollection();
        if ($logContentModels != null) {
            foreach ($logContentModels as $logContent) {
                $sortAbandonedCartId[$logContent->getData('abandonedcart_id')][] = $logContent;
            }
            if (!empty($sortAbandonedCartId)) {
                foreach ($sortAbandonedCartId as $key => $logContents) {
                    if (is_array($logContents)) {
                        foreach ($logContents as $logContent) {
                            $abandonedCart = $abandonedCartCollection->addFieldToFilter('id', $key)->getFirstItem();
                            $quote_id      = $abandonedCart->getData('quote_id');
                            $quote         = $this->_quoteFactory->create()->getCollection()->addFieldToFilter('entity_id', $quote_id)->getFirstItem();
                            $allItems      = $quote->getAllItems();
                            /** @var \Magento\Quote\Model\Quote\Item $item */
                            $count = 0;
                            /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
                            $productRepository = $this->objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface');
                            foreach ($allItems as $item) {
                                /** @var \Magento\Catalog\Model\Product $product */
                                $product     = $item->getProduct();
                                $firstStockItem = $productRepository->getById($product->getId())->getExtensionAttributes()->getStockItem();
                                $is_in_stock = $firstStockItem->getData('is_in_stock');
//                                $inStock = $product->getExtensionAttributes()->getStockItem()->getQty();
                                if ($is_in_stock != "" && $is_in_stock < 1) {
                                    $count++;
                                }
                            }
                            $item_qty = $quote->getItemsQty();
                            if ($item_qty == $count) {
                                $logContent->addData([
                                    'status' => Mail::STATUS_CANCELLED,
                                    'log'    => 'All products went out of stock'
                                ]);
                                $logContent->save();
                            }
                        }
                    }
                }
            }
        }
    }
}