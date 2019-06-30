<?php

namespace App\Command;

use App\Entity\Report;
use App\Repository\ReportRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class CalculateScoreCommand extends Command
{
    protected static $defaultName = 'app:calculate-score';

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CalculateScoreCommand constructor.
     *
     * @param RegistryInterface $doctrine
     * @param LoggerInterface   $logger
     */
    public function __construct(RegistryInterface $doctrine, LoggerInterface $logger)
    {
        parent::__construct(self::$defaultName);
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Calculate score of domains')
            ->addOption('cacheSize', null, InputOption::VALUE_OPTIONAL, 'how much record persist per turn?', 100)
            ->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'how much second timeout between requests', 0)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ReportRepository $reportRepo */
        $reportRepo = $this->doctrine->getRepository('App:Report');
        $io = new SymfonyStyle($input, $output);

        $stopwatch = new Stopwatch();
        $mainTimer = $stopwatch->start('main');
        $this->logger->info(sprintf('Command started at: %s', date('Y-m-d H:i:s')));

        $cacheSize = $input->getOption('cacheSize');
        $timeout = $input->getOption('timeout');

        do {
            $this->logger->info(sprintf('Fetching domains from db (%d domain)', $cacheSize));
            $queue = $reportRepo->getScoreQueue($cacheSize);
            /** @var Report $report */
            foreach ($queue as $report) {
                $this->calculateScore($report);

                if ($timeout) {
                    sleep($timeout);
                }
            }
            try {
                $this->doctrine->getManager()->flush();
            } catch (\Exception $e) {
                $this->logger->error(sprintf('%s: %s', get_class($e), $e->getMessage()), [$e->getTraceAsString()]);
            }

            $this->doctrine->getManager()->clear();

            gc_collect_cycles();
        } while (!empty($queue));
        $mainTimer->stop();
        $this->logger->info(sprintf('Process finished after: %d ms', $mainTimer->getDuration()));
        $this->logger->info(sprintf('Memory usage: %d MB', $mainTimer->getMemory() / 1024 / 1024));
    }

    private function calculateScore(Report $report)
    {
        if (!$report->getGlobalRank()) {
            return;
        }

        $grRank = $report->getGlobalRank() ? $report->getGlobalRank() : 50000000;
        $score = ((100 - (0.8 * (pow(2, log($grRank - 1, 3))) / (pow($grRank, 1 / 3)))) / 6.66666666667);

        $localScore = 0;
        if ($geographies = $report->getGeographies()) {
            foreach ($geographies as $geography) {
                if ('ir' === $geography->getCountry()->getAlpha2() && $geography->getRank()) {
                    $localScore = ((100 - (5 * (pow(2, log($geography->getRank() - 1, 3))) / (pow($geography->getRank(), 1 / 3)))) / 7.14285714286);
                }
            }
        } else {
            $localScore = ((100 - (5 * (pow(2, log(1000000 - 1, 3))) / (pow(1000000, 1 / 3)))) / 7.14285714286);
        }

        $score += $localScore;

        $this->logger->info(sprintf('Set score %f for %s', $score, $report->getDomain()->getDomain()));
        $report->getDomain()->setScore($score);
        $report->getDomain()->setScoreUpdatedAt(new \DateTime('-1 day'));
    }
}
