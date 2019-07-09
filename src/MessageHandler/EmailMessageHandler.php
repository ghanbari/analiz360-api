<?php

namespace App\MessageHandler;

use App\Entity\EmailMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\NamedAddress;

class EmailMessageHandler implements MessageHandlerInterface
{
    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MailerInterface
     */
    private $sender;

    /**
     * @var NamedAddress
     */
    private $defaultSender;

    /**
     * EmailMessageHandler constructor.
     *
     * @param MailerInterface       $sender
     * @param RegistryInterface     $doctrine
     * @param LoggerInterface       $logger
     * @param ParameterBagInterface $parameters
     */
    public function __construct(MailerInterface $sender, RegistryInterface $doctrine, LoggerInterface $logger, ParameterBagInterface $parameters)
    {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->sender = $sender;
        $config = $parameters->get('email');
        $this->defaultSender = new NamedAddress($config['default_sender']['address'], $config['default_sender']['name']);
    }

    public function __invoke(EmailMessage $message)
    {
        $email = (new Email())
            ->to($message->getReceptor())
            ->subject($message->getTemplate()->getName())
            ->text($message->getMessage())
            ->html($message->getMessage())
            ->from($message->getSenderEmail() ?? $this->defaultSender);


        $this->sender->send($email);
        $message->setStatus(EmailMessage::STATUS_SENT);

        $this->doctrine->getManager()->persist($message);
        $this->doctrine->getManager()->flush();
    }
}
