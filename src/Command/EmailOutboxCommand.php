<?php

namespace App\Command;

use App\Entity\EmailMessage;
use App\Repository\EmailMessageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\NamedAddress;

class EmailOutboxCommand extends Command
{
    protected static $defaultName = 'app:email-outbox';

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var array [second ago, second later]
     */
    private $range;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var MailerInterface
     */
    private $sender;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var NamedAddress
     */
    private $defaultSender;

    /**
     * SmsOutboxCommand constructor.
     *
     * @param ParameterBagInterface $parameters
     * @param RegistryInterface     $doctrine
     * @param MailerInterface       $sender
     * @param LoggerInterface       $logger
     */
    public function __construct(
        ParameterBagInterface $parameters,
        RegistryInterface $doctrine,
        MailerInterface $sender,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->sender = $sender;
        $this->logger = $logger;

        $config = $parameters->get('email');
        $this->defaultSender = new NamedAddress($config['default_sender']['address'], $config['default_sender']['name']);
        $this->timeout = isset($config['sender']['timeout']) ? $config['sender']['timeout'] : 300;
        $this->range = isset($config['sender']['range']) ? $config['sender']['range'] : [300, 300];
    }

    protected function configure()
    {
        $this
            ->setDescription('Read outbox and email messages.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EmailMessageRepository $repository */
        $repository = $this->doctrine->getRepository('App:EmailMessage');

        while (true) {
            $result = $repository->pullQueue(10, $this->range[0], $this->range[1]);

            $this->logger->info(sprintf('Pull from queue: %d message', count($result)));

            foreach ($result as $message) {
                $email = (new Email())
                    ->to($message->getReceptor())
                    ->subject($message->getTemplate()->getName())
                    ->text($message->getMessage())
                    ->html($message->getMessage())
                    ->from($message->getSenderEmail() ?? $this->defaultSender);

                $this->sender->send($email);
                $message->setStatus(EmailMessage::STATUS_SENT);
            }

            $this->doctrine->getManager()->flush();

            if (empty($result)) {
                $this->logger->info(sprintf('Sleep sender for about %s seconds', $this->timeout));
                sleep($this->timeout);
            } else {
                $this->logger->info('Persist message status to db.');
                $this->doctrine->getManager()->flush();
                $this->doctrine->resetManager();
            }
        }
    }
}
