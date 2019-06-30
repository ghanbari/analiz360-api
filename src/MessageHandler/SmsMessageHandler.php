<?php

namespace App\MessageHandler;

use App\Entity\SmsMessage;
use App\Sms\SmsProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SmsMessageHandler implements MessageHandlerInterface
{
    /**
     * @var SmsProviderInterface
     */
    private $smsProvider;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SmsMessageHandler constructor.
     *
     * @param SmsProviderInterface $smsProvider
     * @param RegistryInterface    $doctrine
     * @param LoggerInterface      $logger
     */
    public function __construct(SmsProviderInterface $smsProvider, RegistryInterface $doctrine, LoggerInterface $logger)
    {
        $this->smsProvider = $smsProvider;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    public function __invoke(SmsMessage $message)
    {
        $report = $this->smsProvider->send($message);
        $report->setMessage($message);
        $this->doctrine->getManager()->persist($message);
        $this->doctrine->getManager()->persist($report);
        $this->doctrine->getManager()->flush();
    }
}
