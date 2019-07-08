<?php

namespace App\Sms;

use App\Entity\SmsMessage;
use App\Entity\SmsOutbox;
use Kavenegar\KavenegarApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Kavenegar implements SmsProviderInterface
{
    private $config;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var KavenegarApi
     */
    private $api;

    /**
     * Kavenegar constructor.
     *
     * @param ParameterBagInterface $parameters
     * @param LoggerInterface       $logger
     */
    public function __construct(ParameterBagInterface $parameters, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $config = $parameters->get('sms');
        $this->config = $config['providers']['kavenegar'];
        $this->api = new KavenegarApi($this->config['api_key']);
    }

    public function send(SmsMessage $message): ? SmsOutbox
    {
        try {
            $this->logger->info(sprintf('Send message to %s', $message->getReceptor()));
            $result = $this->api->send(
                $this->config['sender_number'],
                $message->getReceptor(),
                $message->getMessage(),
                null,
                null,
                $message->getId()
            );

            $outbox = new SmsOutbox();
            if ($message->getCreatedBy()) {
                $outbox->setCreatedBy($message->getCreatedBy());
            }
            $outbox->setSender($this->config['sender_number']);
            $outbox->setStatus(SmsOutbox::STATUS_IN_QUEUE);
            $outbox->setSendTime(new \DateTime());
            $this->logger->info(sprintf('Result for message sent to %s', $message->getReceptor()), $result);

            if (is_array($result)) {
                $result = $result[0];
                $outbox->setCost($result->cost);
                $outbox->setTrackingCode($result->messageid);
            }
        } catch (\Exception $e) {
            $outbox = new SmsOutbox();
            if ($message->getCreatedBy()) {
                $outbox->setCreatedBy($message->getCreatedBy());
            }
            $outbox->setSender($this->config['sender_number']);
            $outbox->setStatus(SmsOutbox::STATUS_FAILED_ON_SEND);
            $outbox->setSendTime(new \DateTime());
            $this->logger->error(
                sprintf(
                    'Can not send message to %s(%s: %s)',
                    $message->getReceptor(),
                    get_class($e),
                    $e->getMessage()
                ),
                [$e->getTraceAsString()]
            );
        }

        return $outbox;
    }

    public function sendBatch(array $messages)
    {
        // TODO: Implement sendBatch() method.
    }

    public function checkStatus(SmsOutbox $report)
    {
        try {
            $report->increaseStatusCheckCount();
            $result = $this->api->Status($report->getTrackingCode());
            if (is_array($result)) {
                $status = $this->convertStatus($result[0]->status);
                $report->setStatus($status);
            }
        } catch (\Exception $e) {
        }
    }

    private function convertStatus($status)
    {
        switch ($status) {
            case 1:
                return SmsOutbox::STATUS_IN_QUEUE;
            case 2:
                return SmsOutbox::STATUS_SCHEDULED;
            case 4:
            case 5:
                return SmsOutbox::STATUS_SEND_TO_TELECOMS;
            case 10:
                return SmsOutbox::STATUS_DELIVERED;
            case 6:
            case 11:
            case 13:
                return SmsOutbox::STATUS_UNDELIVERED;
            case 14:
                return SmsOutbox::STATUS_BLOCKED;
        }
    }

    public function support($provider)
    {
        return 'kavenegar' == $provider;
    }
}
