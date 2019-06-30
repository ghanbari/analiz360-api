<?php

namespace App\Command;

use App\Repository\SmsOutboxRepository;
use App\Sms\SmsProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SmsCheckStatusCommand extends Command
{
    protected static $defaultName = 'app:sms-check-status';

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SmsProviderInterface
     */
    private $provider;

    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var int
     */
    private $maxCheckCount;

    /**
     * SmsCheckStatusCommand constructor.
     *
     * @param ParameterBagInterface $parameters
     * @param RegistryInterface     $doctrine
     * @param LoggerInterface       $logger
     * @param SmsProviderInterface  $sms
     */
    public function __construct(
        ParameterBagInterface $parameters,
        RegistryInterface $doctrine,
        LoggerInterface $logger,
        SmsProviderInterface $sms
    ) {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->provider = $sms;
        $this->parameters = $parameters;

        $config = $parameters->get('sms');
        $this->timeout = isset($config['status_checker']['timeout']) ? $config['status_checker']['timeout'] : 150;
        $this->maxCheckCount = isset($config['status_checker']['max_check_count']) ? $config['status_checker']['max_check_count'] : 3;
    }

    protected function configure()
    {
        $this
            ->setDescription('Update status of messages.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SmsOutboxRepository $repository */
        $repository = $this->doctrine->getRepository('App:SmsOutbox');

        while (true) {
            $result = $repository->getUndefinedStatus(10, $this->maxCheckCount);
            $this->logger->debug(sprintf('Get messages that its status is not finished: %d message', count($result)));

            foreach ($result as $report) {
                $this->provider->checkStatus($report);
            }

            if (empty($result)) {
                $this->logger->debug(sprintf('Sleep checker for about %s seconds', $this->timeout));
                sleep($this->timeout);
            } else {
                $this->logger->debug('Persist message status to db.');
                $this->doctrine->getManager()->flush();
                $this->doctrine->resetManager();
            }
        }
    }
}
