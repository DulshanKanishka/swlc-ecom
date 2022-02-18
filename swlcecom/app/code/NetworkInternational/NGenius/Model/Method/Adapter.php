<?php

namespace NetworkInternational\NGenius\Model\Method;

use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\InfoInterface;
use Psr\Log\LoggerInterface;

class Adapter extends  \Magento\Payment\Model\Method\Adapter
{
    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * @var \Magento\Payment\Gateway\Command\CommandManagerInterface
     */
    private $commandExecutor;

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var ValueHandlerPoolInterface
     */
    private $valueHandlerPool;

    public function __construct(ManagerInterface $eventManager, ValueHandlerPoolInterface $valueHandlerPool, PaymentDataObjectFactory $paymentDataObjectFactory, $code, $formBlockType, $infoBlockType, CommandPoolInterface $commandPool = null, ValidatorPoolInterface $validatorPool = null, CommandManagerInterface $commandExecutor = null, LoggerInterface $logger = null)
    {
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->commandExecutor = $commandExecutor;
        $this->commandPool = $commandPool;
        $this->valueHandlerPool = $valueHandlerPool;
        parent::__construct($eventManager, $valueHandlerPool, $paymentDataObjectFactory, $code, $formBlockType, $infoBlockType, $commandPool, $validatorPool, $commandExecutor, $logger);
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function initialize($paymentAction, $stateObject)
    {
        $this->executeCommand(
            'initialize',
            [
                'payment' => $this->getInfoInstance(),
                'paymentAction' => $paymentAction,
                'stateObject' => $stateObject,
                'amount' => $this->getInfoInstance()->getAmountOrdered()
            ]
        );
        return $this;
    }

    private function executeCommand($commandCode, array $arguments = [])
    {
        if (!$this->canPerformCommand($commandCode)) {
            return null;
        }

        /** @var InfoInterface|null $payment */
        $payment = null;
        if (isset($arguments['payment']) && $arguments['payment'] instanceof InfoInterface) {
            $payment = $arguments['payment'];
            $arguments['payment'] = $this->paymentDataObjectFactory->create($arguments['payment']);
        }

        if ($this->commandExecutor !== null) {
            return $this->commandExecutor->executeByCode($commandCode, $payment, $arguments);
        }

        if ($this->commandPool === null) {
            throw new \DomainException('Command pool is not configured for use.');
        }

        $command = $this->commandPool->get($commandCode);

        return $command->execute($arguments);
    }

    /**
     * Whether payment command is supported and can be executed
     *
     * @param string $commandCode
     * @return bool
     */
    private function canPerformCommand($commandCode)
    {
        return (bool)$this->getConfiguredValue('can_' . $commandCode);
    }

    /**
     * Unifies configured value handling logic
     *
     * @param string $field
     * @param null $storeId
     * @return mixed
     */
    private function getConfiguredValue($field, $storeId = null)
    {
        $handler = $this->valueHandlerPool->get($field);
        $subject = [
            'field' => $field
        ];

        if ($this->getInfoInstance()) {
            $subject['payment'] = $this->paymentDataObjectFactory->create($this->getInfoInstance());
        }

        return $handler->handle($subject, $storeId ?: $this->getStore());
    }
}
