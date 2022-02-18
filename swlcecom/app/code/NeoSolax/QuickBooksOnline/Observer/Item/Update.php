<?php

namespace NeoSolax\QuickBooksOnline\Observer\Item;

use Magenest\QuickBooksOnline\Model\Config;
use Magenest\QuickBooksOnline\Model\QueueFactory;
use Magenest\QuickBooksOnline\Model\Synchronization\Item;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Spatie\Async\Pool;

class Update extends \Magenest\QuickBooksOnline\Observer\Item\Update
{
    public function __construct(
        Pool $pool,
        ManagerInterface $messageManager,
        Config $config,
        QueueFactory $queueFactory,
        Item $item,
        Registry $registry
    ) {
        $this->pool = $pool;
        parent::__construct($messageManager, $config, $queueFactory, $item, $registry);
    }

    public function execute(Observer $observer)
    {
        if ($this->isConnected() && $this->isConnected() == 1) {
            try {
                $product = $observer->getEvent()->getProduct();
                $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');
                if ($product->getIsDuplicate()) {
                    $registryObject->register('is_duplicate', true);
                }
                $id = $product->getId();
                if ($id && $this->isEnabled() && $product->getTypeId() != 'grouped') {
                    if ($this->isImmediatelyMode()) {
                        if ($registryObject->registry('check_to_syn') == null) {
                            if ($product->getTypeId() == 'configurable') {
                                $this->pool
                                    ->add(function ($id) {
                                        $this->_item->sync($id, true);
                                    })
                                    ->catch(function ($exception) {
                                        echo $exception;
                                    });
                                $arrId = $this->registry->registry('arr_id' . $id);
                                if (!empty($arrId)) {
                                    foreach ($arrId as $qboId) {
                                        $this->messageManager->addSuccessMessage(
                                            __('Successfully updated this product (Id: %1) in QuickBooksOnline.', $qboId)
                                        );
                                    }
                                }
                            } else {
                                $this->pool
                                    ->add(function ($id) {
                                        $this->_item->sync($id, true);
                                    })
                                    ->then(function ($qboId) {
                                        $this->messageManager->addSuccessMessage(
                                            __('Successfully updated this product (Id: %1) in QuickBooksOnline.', $qboId)
                                        );
                                    })
                                    ->catch(function ($exception) {
                                        echo $exception;
                                    });
                            }
                        }
                    } else {
                        $this->addToQueue($id);
                    }
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
    }
}
