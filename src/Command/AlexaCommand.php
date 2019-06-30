<?php

namespace App\Command;

use App\Crawler\AlexaCrawler;
use App\Crawler\Crawler;
use App\Entity\Backlink;
use App\Entity\Domain;
use App\Entity\Downstream;
use App\Entity\Geography;
use App\Entity\Keyword;
use App\Entity\RelatedDomain;
use App\Entity\Report;
use App\Entity\Toppage;
use App\Entity\Upstream;
use App\Exception\Crawler\DataNotFoundException;
use App\Exception\Proxy\ProxyNotFoundException;
use App\Proxy\ProxyManager;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Processor\TagProcessor;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AlexaCommand extends Command
{
    protected static $defaultName = 'alexa:report';

    /**
     * @var AlexaCrawler
     */
    private $crawler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var ProxyManager
     */
    private $proxyManager;

    /**
     * @var array
     */
    private $crawlerConfig;

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var UidProcessor
     */
    private $uidProcessor;

    /**
     * @var TagProcessor
     */
    private $tagProcessor;
    /**
     * @var FingersCrossedHandler
     */
    private $errorHandler;

    public function __construct(RegistryInterface $doctrine, AlexaCrawler $crawler, LoggerInterface $alexaLogger, ValidatorInterface $validator, ProxyManager $proxyManager, $crawlerConfig, UidProcessor $uidProcessor, TagProcessor $tagProcessor, FingersCrossedHandler $errorHandler)
    {
        $this->crawler = $crawler;

        parent::__construct();
        $this->logger = $alexaLogger;
        $this->validator = $validator;
        $this->proxyManager = $proxyManager;
        $this->crawlerConfig = $crawlerConfig;
        $this->doctrine = $doctrine;
        $this->uidProcessor = $uidProcessor;
        $this->tagProcessor = $tagProcessor;
        $this->errorHandler = $errorHandler;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawl alexa & Update all registered domains')
            ->addArgument('domains', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'which site you would like to get its information')
            ->addOption('cache-size', 'c', InputOption::VALUE_OPTIONAL, 'how much record persist per turn?', 100)
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'how much second timeout between requests', 0)
            ->addOption('proxy', 'p', InputOption::VALUE_OPTIONAL, 'Send requests by proxy?', 'on')
            ->addOption('max-allowed-memory', 'm', InputOption::VALUE_OPTIONAL, 'Max allowed memory by this script', '500M')
        ;
    }

    // TODO: insert total alexa call in db
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $today = date('d');
        $io = new SymfonyStyle($input, $output);

        if ('on' !== $input->getOption('proxy')) {
            $this->crawler->setProxyStatus(Crawler::WITHOUT_PROXY);
        }

        $stopwatch = new Stopwatch();
        $mainTimer = $stopwatch->start('main');
        $this->logger->info('Alexa command started at: {date}', ['date' => date('Y-m-d H:i:s')]);

        $domains = $input->getArgument('domains');
        $cacheSize = $input->getOption('cache-size');
        $timeout = $input->getOption('timeout');
        $allowedMemory = $input->getOption('max-allowed-memory');
        $memoryUnit = strtoupper(substr($allowedMemory, -1, 1));
        $allowedMemory = floatval($allowedMemory) * ('M' === $memoryUnit ? 1024 * 1024 : (('G' === $memoryUnit) ? 1024 * 1024 * 1024 : 1024));

        if (empty($domains)) {
            do {
                $this->logger->info('Fetching domains from db ({cacheSize} domain)', ['cacheSize' => $cacheSize]);
                $queue = $this->doctrine->getRepository('App\Entity\Domain')->getQueue($cacheSize, $this->crawlerConfig['failed']['try_after']);
                $this->logger->info('{count} domain is in queue.', ['count' => count($queue)]);
                $this->errorHandler->reset();
                /** @var Domain $domain */
                foreach ($queue as $domain) {
                    $this->tagProcessor->setTags(['crawler' => 'alexa', 'domain' => $domain->getDomain()]);
                    $this->createReport($domain, $io);

                    if ($timeout) {
                        sleep($timeout);
                    }

                    $this->uidProcessor->reset();
                    $this->errorHandler->clear();
                }
                try {
                    $this->doctrine->getManager()->flush();
                } catch (UniqueConstraintViolationException $e) {
                    $this->doctrine->resetManager();
                    $this->logger->notice(
                        'Duplicate report for {domain}',
                        [
                            'domain' => $domain->getDomain(),
                            'error.message' => $e->getMessage(),
                            'error.stack' => $e->getTrace(),
                            'error.kind' => get_class($e),
                        ]
                    );
                } catch (\Exception $e) {
                    $this->doctrine->resetManager();
                    $this->logger->error(
                        'Database error',
                        [
                            'domain' => $domain->getDomain(),
                            'error.message' => $e->getMessage(),
                            'error.stack' => $e->getTrace(),
                            'error.kind' => get_class($e),
                        ]
                    );
                }
                $this->doctrine->getManager()->clear("App\Entity\Report");
                $this->doctrine->getManager()->clear("App\Entity\RelatedDomain");

                if (date('d') !== $today) {
                    $this->logger->warning('Have a good night! bye.');
                    exit();
                }

                gc_collect_cycles();

                if (($allowedMemory - (10 * $allowedMemory / 100)) < memory_get_peak_usage(true)) {
                    $this->logger->warning(
                        'Process reach to max allowed memory',
                        ['current' => memory_get_peak_usage(true), 'allowed' => $allowedMemory]
                    );
                    break;
                }
            } while (!empty($queue));
            $mainTimer->stop();
            $this->logger->info(sprintf('Process finished after: %d ms', $mainTimer->getDuration()));
            $this->logger->info(sprintf('Memory usage: %d MB', $mainTimer->getMemory() / 1024 / 1024));
        } else {
            $roundTimer = $mainTimer->lap('main');
            foreach ($domains as $domain) {
                try {
                    $roundTimer->start();
                    $report = $this->crawler->getBasicInfo($domain);
                    $io->title('Basic Result for:'.$domain);
                    $io->table(array_keys($report), [$report]);

                    $geographies = $this->crawler->getGeographies($domain, true);
                    $io->title('Geography Result for:'.$domain);
                    $io->table(['country', 'visitorsPercent', 'pageViewsPerUser', 'pageViewsPercent', 'rank'], $geographies);

                    $relatedDomains = $this->crawler->getRelatedDomains($domain);
                    $io->title('Related domains Result for:'.$domain);
                    $io->table(['score', 'domain'], $relatedDomains);

                    $keywords = $this->crawler->getKeywords($domain);
                    $io->title('Keyword Result for:'.$domain);
                    $io->table(['keyword', 'percent', 'sharePercent'], $keywords);

                    $upstreams = $this->crawler->getUpstreams($domain);
                    $io->title('Upstream Result for:'.$domain);
                    $io->table(['domain', 'percent'], $upstreams);

                    $downstreams = $this->crawler->getDownstreams($domain);
                    $io->title('Downstream Result for:'.$domain);
                    $io->table(['domain', 'percent'], $downstreams);

                    $backlinks = $this->crawler->getBacklinks($domain);
                    $io->title('Backlinks Result for:'.$domain);
                    $io->table(['rank', 'domain', 'page'], $backlinks);

                    $toppages = $this->crawler->getToppages($domain);
                    $io->title('Top pages Result for:'.$domain);
                    $io->table(['address', 'percent'], $toppages);

                    $io->note(sprintf('%s fetched in %d ms', $domain, $roundTimer->getDuration()));
                    $roundTimer->stop();
                    if ($timeout) {
                        sleep($timeout);
                    }
                } catch (\Exception $e) {
                    $io->getErrorStyle()->warning(sprintf('%s (%s): %s', $e->getMessage(), get_class($e), $e->getTraceAsString()));
                    $this->logger->warning(sprintf('%s (%s): %s', $e->getMessage(), get_class($e), $e->getTraceAsString()));
                }
            }
            $io->note('Process time:'.$mainTimer->getDuration());
        }
    }

    private function isValid($entity, $domain)
    {
        try {
            $errors = $this->validator->validate($entity);
            if (count($errors) > 0) {
                $errorStrings = [];
                /** @var ConstraintViolationInterface $error */
                foreach ($errors as $error) {
                    $errorStrings[] = sprintf('%s: %s (%s)', $error->getPropertyPath(), $error->getMessage(), $error->getInvalidValue());
                }
                $this->logger->warning(
                    'Fetching {entity} information for {domain} failed',
                    [
                        'entity' => get_class($entity),
                        'domain' => $domain,
                        'violations' => $errorStrings,
                    ]
                );
            }

            return 0 == count($errors);
        } catch (\Exception $e) {
            $this->logger->error(
                'Validation failed.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );

            return false;
        }
    }

    private function createReport(Domain $domain, SymfonyStyle $io)
    {
        try {
            $roundTimer = new Stopwatch();
            $roundTimer->start('round');

            $this->logger->info('Parse Basic info: {domain}', ['domain' => $domain->getDomain()]);
            $report = $this->crawler->getBasicInfo($domain->getDomain());

            $this->logger->info('Parse Geo info: {domain}', ['domain' => $domain->getDomain()]);
            $geographies = $this->crawler->getGeographies($domain->getDomain(), false);

            $this->logger->info('Parse Related domains info: {domain}', ['domain' => $domain->getDomain()]);
            $relatedDomains = $this->crawler->getRelatedDomains($domain->getDomain());

            $this->logger->info('Parse Keyword info: {domain}', ['domain' => $domain->getDomain()]);
            $keywords = $this->crawler->getKeywords($domain->getDomain());

            $this->logger->info('Parse Upstream info: {domain}', ['domain' => $domain->getDomain()]);
            $upstreams = $this->crawler->getUpstreams($domain->getDomain());

            $this->logger->info('Parse Downstream info: {domain}', ['domain' => $domain->getDomain()]);
            $downstreams = $this->crawler->getDownstreams($domain->getDomain());

            $this->logger->info('Parse BackLink info: {domain}', ['domain' => $domain->getDomain()]);
            $backlinks = $this->crawler->getBacklinks($domain->getDomain());

            $this->logger->info('Parse TopPage info: {domain}', ['domain' => $domain->getDomain()]);
            $toppages = $this->crawler->getToppages($domain->getDomain());

            $report = Report::create(array_merge($report, ['domain' => $domain]));
            if ($this->isValid($report, $domain->getDomain())) {
                $this->doctrine->getManager()->persist($report);
                $report->getDomain()->setLastReportStatus(Domain::REPORT_FINISHED);
                $this->logger->info('Report is valid and persisted');
            } else {
                $this->logger->warning('Report is invalid and skipped');
                $report->getDomain()->setLastReportStatus(Domain::REPORT_FAILED);

                return false;
            }

            foreach ($geographies as $geography) {
                $geo = Geography::create(array_merge($geography, ['report' => $report]));
                if ($this->isValid($geo, $domain->getDomain())) {
                    $this->doctrine->getManager()->persist($geo);
                } else {
                    $report->removeGeography($geo);
                }
            }

            foreach ($relatedDomains as $related) {
                $match = [];
                preg_match('/^(?!:\/\/)(?:[a-zA-Z0-9-_]+\.)*([a-zA-Z0-9][a-zA-Z0-9-_]+\.[a-zA-Z]{2,11}?)$/i', $related['domain'], $match);

                if (!isset($match[1])) {
                    continue;
                }

                $relationTo = new RelatedDomain($domain, $match[1], RelatedDomain::SOURCE_ALEXA, $related['score']);
                $this->doctrine->getManager()->persist($relationTo);
            }

            foreach ($keywords as $keyword) {
                $kw = Keyword::create(array_merge($keyword, ['report' => $report]));
                if ($this->isValid($kw, $domain->getDomain())) {
                    $this->doctrine->getManager()->persist($kw);
                } else {
                    $report->removeKeyword($kw);
                }
            }

            foreach ($upstreams as $upstream) {
                $us = Upstream::create(array_merge($upstream, ['report' => $report]));
                if ($this->isValid($us, $domain->getDomain())) {
                    $this->doctrine->getManager()->persist($us);
                } else {
                    $report->removeUpstream($us);
                }
            }

            foreach ($downstreams as $downstream) {
                $down = Downstream::create(array_merge($downstream, ['report' => $report]));
                if ($this->isValid($down, $domain->getDomain())) {
                    $this->doctrine->getManager()->persist($down);
                } else {
                    $report->removeUpstream($down);
                }
            }

            foreach ($backlinks as $backlink) {
                $bl = Backlink::create(array_merge($backlink, ['report' => $report]));
                if ($this->isValid($bl, $domain->getDomain())) {
                    $this->doctrine->getManager()->persist($bl);
                } else {
                    $report->removeBacklink($bl);
                }
            }

            foreach ($toppages as $toppage) {
                $tp = Toppage::create(array_merge($toppage, ['report' => $report]));
                if ($this->isValid($tp, $domain->getDomain())) {
                    $this->doctrine->getManager()->persist($tp);
                } else {
                    $report->removeToppage($tp);
                }
            }

            $roundTimer->stop('round');
            $this->logger->info(
                'Fetching {domain} information in {duration} ms, {memory} MB was usages',
                [
                    'domain' => $domain->getDomain(),
                    'duration' => $roundTimer->getEvent('round')->getDuration(),
                    'memory' => $roundTimer->getEvent('round')->getMemory() / 1024 / 1024,
                ]
            );
            $roundTimer->reset();
        } catch (ProxyNotFoundException $e) {
            $this->logger->error(
                'Proxy not found',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        } catch (DataNotFoundException $e) {
            $domain->setLastReportStatus(Domain::REPORT_NOT_FOUND);
            $this->logger->notice(
                'Data is not exists for {domain}',
                [
                    'domain' => $domain->getDomain(),
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        } catch (ConnectException | RequestException $e) {
            $this->logger->error(
                'Connection Error: We can not fetch data for {domain}, changing proxy...',
                [
                    'domain' => $domain->getDomain(),
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
            $this->proxyManager->getProxy(false, true);
        } catch (\Exception $e/*TODO: create appropriate class for crawler exceptions*/) {
            $this->logger->error(
                'We can not fetch data for {domain}',
                [
                    'domain' => $domain->getDomain(),
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }

        return true;
    }
}
