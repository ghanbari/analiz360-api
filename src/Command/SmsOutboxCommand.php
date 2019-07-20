<?php

namespace App\Command;

use App\Repository\SmsMessageRepository;
use App\Sms\SmsProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SmsOutboxCommand extends Command
{
    protected static $defaultName = 'app:sms-outbox';

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
     * @var SmsProviderInterface
     */
    private $sender;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SmsOutboxCommand constructor.
     *
     * @param ParameterBagInterface $parameters
     * @param RegistryInterface     $doctrine
     * @param SmsProviderInterface  $sender
     * @param LoggerInterface       $messengerLogger
     */
    public function __construct(
        ParameterBagInterface $parameters,
        RegistryInterface $doctrine,
        SmsProviderInterface $sender,
        LoggerInterface $messengerLogger
    ) {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->sender = $sender;
        $this->logger = $messengerLogger;

        $config = $parameters->get('sms');
        $this->timeout = isset($config['sender']['timeout']) ? $config['sender']['timeout'] : 300;
        $this->range = isset($config['sender']['range']) ? $config['sender']['range'] : [300, 300];
    }

    protected function configure()
    {
        $this
            ->setDescription('Read outbox and send messages.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SmsMessageRepository $repository */
        $repository = $this->doctrine->getRepository('App:SmsMessage');

        while (true) {
            $result = $repository->pullQueue(10, $this->range[0], $this->range[1]);

            $this->logger->info(sprintf('Pull from queue: %d message', count($result)));

            foreach ($result as $message) {
                $outbox = $this->sender->send($message);
                if ($outbox) {
                    $this->doctrine->getManager()->persist($outbox);
                    $outbox->setMessage($message);
                }
            }

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
