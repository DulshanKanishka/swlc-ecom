<?php

namespace Magenest\AbandonedCart\Plugin;

class LogCron
{
    protected $_logCronFactory;

    /**
     * LogCron constructor.
     *
     * @param \Magenest\AbandonedCart\Model\LogCronFactory $logCronFactory
     */
    public function __construct(
        \Magenest\AbandonedCart\Model\LogCronFactory $logCronFactory
    ) {
        $this->_logCronFactory = $logCronFactory;
    }

    public function afterSave(\Magento\Cron\Model\Schedule $subject, $result)
    {
        $model = $this->_logCronFactory->create();
        if ($subject->getJobCode() == 'abandonedcart_collect_data'
            || $subject->getJobCode() == 'abandonedcart_send_email'
            || $subject->getJobCode() == 'abandonedcart_send_sms'
            || $subject->getJobCode() == 'abandonedcart_cancel_condition'
        ) {
            $data         = [
                'message'     => $subject->getMessages(),
                'magento_id'  => $subject->getScheduleId(),
                'type'        => $subject->getJobCode(),
                'status'      => $subject->getStatus(),
                'executed_at' => $subject->getExecutedAt(),
                'finished_at' => $subject->getFinishedAt()
            ];
            $logCronModel = $model;
            $logCronModel->setData($data)->save();
        }
        return $result;
    }
}