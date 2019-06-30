<?php

namespace App\Command;

use App\Crawler\Crawler;
use App\Crawler\DomainAnalyzerInterface;
use App\Entity\Domain;
use App\Entity\DomainAudit;
use App\Exception\Crawler\DataNotFoundException;
use App\Proxy\ProxyManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class DomainAnalyzerCommand extends Command
{
    protected static $defaultName = 'app:analyze';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var iterable
     */
    private $analyzers;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var ProxyManager
     */
    private $proxyManager;

    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    /**
     * DomainAnalyzerCommand constructor.
     *
     * @param iterable              $analyzers
     * @param RegistryInterface     $doctrine
     * @param LoggerInterface       $crawlerLogger
     * @param ProxyManager          $proxyManager
     * @param ParameterBagInterface $parameters
     */
    public function __construct(iterable $analyzers, RegistryInterface $doctrine, LoggerInterface $crawlerLogger, ProxyManager $proxyManager, ParameterBagInterface $parameters)
    {
        parent::__construct(self::$defaultName);
        $this->logger = $crawlerLogger;
        $this->doctrine = $doctrine;
        $this->proxyManager = $proxyManager;
        $this->analyzers = $analyzers;
        $this->parameters = $parameters;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawl alexa & Update all registered domains')
            ->addArgument('domains', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'which site you would like to get its information')
            ->addOption('cacheSize', null, InputOption::VALUE_OPTIONAL, 'how much record persist per turn?', 100)
            ->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'how much second timeout between requests', 0)
            ->addOption('proxy', 'p', InputOption::VALUE_OPTIONAL, 'Send requests by proxy?', 'on')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        /** @var DomainAnalyzerInterface $analyzer */
        foreach ($this->analyzers as $analyzer) {
            if ($analyzer instanceof Crawler) {
                if ('on' !== $input->getOption('proxy')) {
                    $analyzer->setProxyStatus(Crawler::WITHOUT_PROXY);
                }
            }
        }

        $stopwatch = new Stopwatch();
        $mainTimer = $stopwatch->start('main');
        $this->logger->info(sprintf('Command started at: %s', date('Y-m-d H:i:s')));

        $domains = $input->getArgument('domains');
        $cacheSize = $input->getOption('cacheSize');
        $timeout = $input->getOption('timeout');

        if (empty($domains)) {
            do {
                $this->logger->info(sprintf('Fetching domains from db (%d domain)', $cacheSize));
                $queue = $this->doctrine->getRepository('App\Entity\Domain')->getAuditQueue($cacheSize);
                /** @var Domain $domain */
                foreach ($queue as $domain) {
                    $data = [];
                    /** @var DomainAnalyzerInterface $analyzer */
                    foreach ($this->analyzers as $analyzer) {
                        try {
                            $this->logger->info(sprintf('Call %s analyzer for %s', get_class($analyzer), $domain->getDomain()));
                            $result = $analyzer->analyze($domain);
                            if ($result) {
                                $this->logger->info(sprintf('Merge %s analyzer data with other analyzers', get_class($analyzer)));
                                $data = array_merge_recursive($result, $data);
                            }
                        } catch (\Exception $e) {
                            $this->logger->error(
                                sprintf('Analyzers %s failed: %s', get_class($analyzer), $e->getMessage()),
                                [$e->getTraceAsString()]
                            );
                        }
                    }

                    if (empty($data)) {
                        throw new DataNotFoundException(sprintf('Domain audit data is not found'));
                    }

                    $audit = new DomainAudit();
                    $audit->setDomain($domain)
                        ->setData($data)
                        ->fixData()
                        ->calculateScore();
                    $this->doctrine->getManager()->persist($audit);
                    $domain->setLastAuditStatus(Domain::REPORT_FINISHED);
                    $this->logger->info(sprintf('Persist audit for %s', $domain->getDomain()));

                    if ($timeout) {
                        sleep($timeout);
                    }
                }
                try {
                    $this->logger->info(sprintf('Flush entity manager'));
                    $this->doctrine->getManager()->flush();
                } catch (DataNotFoundException $e) {
                    $domain->setLastAuditStatus(Domain::REPORT_NOT_FOUND);
                    $this->logger->error(sprintf('Data is not exists for %s', $domain->getDomain()), [$e->getTraceAsString()]);
                } catch (UniqueConstraintViolationException $e) {
                    $this->doctrine->resetManager();
                    $this->logger->warning(sprintf('Duplicate report: %s', $e->getMessage()), [$e->getTraceAsString()]);
                } catch (\Exception $e) {
                    $this->logger->error(sprintf('%s: %s', get_class($e), $e->getMessage()), [$e->getTraceAsString()]);
                }
                $this->doctrine->getManager()->clear("App\Entity\DomainAudit");

                gc_collect_cycles();
            } while (!empty($queue));
            $mainTimer->stop();
            $this->logger->info(sprintf('Process finished after: %d ms', $mainTimer->getDuration()));
            $this->logger->info(sprintf('Memory usage: %d MB', $mainTimer->getMemory() / 1024 / 1024));
        } else {
            $buffer = new BufferedOutput(OutputInterface::VERBOSITY_VERY_VERBOSE);
            $io = new SymfonyStyle($input, $buffer);
            $domainsResults = [];
            $this->logger->info(sprintf('Fetching domains from db (%s)', join(',', $domains)));
            $queue = $this->doctrine->getRepository('App\Entity\Domain')->findByDomain($domains);

            /** @var Domain $domain */
            foreach ($queue as $domain) {
                $data = [];
                /** @var DomainAnalyzerInterface $analyzer */
                foreach ($this->analyzers as $analyzer) {
                    try {
                        $this->logger->info(sprintf('Call %s analyzer for %s', get_class($analyzer), $domain->getDomain()));
                        $result = $analyzer->analyze($domain);
                        if ($result) {
                            $this->logger->info(sprintf('Merge %s analyzer data with other analyzers', get_class($analyzer)));
                            $data = array_merge_recursive($result, $data);
                        }
                    } catch (\Exception $e) {
                        $this->logger->error(
                            sprintf('Analyzers %s failed: %s', get_class($analyzer), $e->getMessage()),
                            [$e->getTraceAsString()]
                        );
                    }
                }

                if (isset($result)) {
                    $audit = new DomainAudit();
                    $audit->setDomain($domain)
                        ->setData($data)
                        ->fixData()
                        ->calculateScore();

                    $res = [];
                    $res['score'] = $audit->getScore();
                    $res['domain'] = $domain->getDomain();

                    foreach ($result as $group) {
                        foreach ($group as $name => $item) {
                            $res[$name] = $item['score'];
                        }
                    }

                    foreach ($audit->getCategoriesScore() as $group => $score) {
                        $res['score_'.$group] = sprintf('sum: %f, count: %f', $score['sum'], $score['count']);
                    }

                    $domainsResults[] = $res;
                }

                $this->logger->info(sprintf('Persist audit for %s', $domain->getDomain()));

                if ($timeout) {
                    sleep($timeout);
                }
            }

            if (isset($domainsResults)) {
                $io->table(array_keys($domainsResults[0]), $domainsResults);
            }

            $name = 'audit_'.date('Y-m-d H:i:s').'.txt';
            $filename = join(DIRECTORY_SEPARATOR, [$this->parameters->get('kernel.project_dir'), 'var', $name]);
            file_put_contents($filename, $buffer->fetch());

            gc_collect_cycles();
            $mainTimer->stop();
            $this->logger->info(sprintf('Process finished after: %d ms', $mainTimer->getDuration()));
            $this->logger->info(sprintf('Memory usage: %d MB', $mainTimer->getMemory() / 1024 / 1024));
        }
    }
}
