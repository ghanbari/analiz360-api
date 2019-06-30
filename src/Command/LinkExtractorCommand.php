<?php

namespace App\Command;

use App\Crawler\Crawler;
use App\Crawler\WebsiteCrawler;
use App\Exception\Crawler\DataNotFoundException;
use App\Exception\Proxy\ProxyNotFoundException;
use App\Proxy\ProxyManager;
use Doctrine\Bundle\DoctrineBundle\Registry;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LinkExtractorCommand extends Command
{
    protected static $defaultName = 'app:link:exporter';

    /**
     * @var WebsiteCrawler
     */
    private $crawler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProxyManager
     */
    private $proxyManager;

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var array
     */
    private $pages = [];

    private $visitedPages = [];

    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    /**
     * @var array
     */
    private $links = [];

    /**
     * LinkExtractorCommand constructor.
     *
     * @param RegistryInterface     $doctrine
     * @param WebsiteCrawler        $crawler
     * @param LoggerInterface       $crawlerLogger
     * @param ProxyManager          $proxyManager
     * @param ParameterBagInterface $parameters
     */
    public function __construct(RegistryInterface $doctrine, WebsiteCrawler $crawler, LoggerInterface $crawlerLogger, ProxyManager $proxyManager, ParameterBagInterface $parameters)
    {
        $this->crawler = $crawler;

        parent::__construct();
        $this->logger = $crawlerLogger;
        $this->proxyManager = $proxyManager;
        $this->doctrine = $doctrine;
        $this->parameters = $parameters;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawl an website and extract information')
            ->addArgument('address', InputArgument::REQUIRED, 'which page you would like to start crawling')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'filename that we must save information?', '')
            ->addOption('patterns', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Only page that its address match this pattern will be scanned', [])
            ->addOption('proxy', 'p', InputOption::VALUE_OPTIONAL, 'Send requests by proxy?', 'on')
        ;
    }

    // TODO: insert total alexa call in db
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ('on' !== $input->getOption('proxy')) {
            $this->crawler->setProxyStatus(Crawler::WITHOUT_PROXY);
        }

        $io = new SymfonyStyle($input, $output);
        $mainDomain = parse_url($input->getArgument('address'), PHP_URL_HOST);
        $this->pages[] = $input->getArgument('address');
        $patterns = $input->getOption('patterns');
        $filename = join(DIRECTORY_SEPARATOR, [$this->parameters->get('kernel.project_dir'), 'var', $input->getOption('output')]);
        $file = fopen($filename, 'a');
        $i = 0;

        try {
            do {
                $address = $this->pages[$i];
                $this->visitedPages[] = $address;
                $io->writeln(sprintf('fetch & parse %s', $address));
                $crawler = $this->crawler->getCrawler($address);
                $links = $crawler->filter('a')->links();
                $io->writeln(sprintf('Find %d link', count($links)));
                foreach ($links as $link) {
                    if (count($patterns) > 0) {
                        foreach ($patterns as $pattern) {
                            if (preg_match("@$pattern@i", $link->getUri())) {
                                $uri = $link->getUri();
                                if (!in_array($uri, $this->visitedPages) && !in_array($uri, $this->pages)) {
                                    $this->pages[] = $uri;
                                }
                                break;
                            }
                        }
                    } else {
                        $uri = $link->getUri();
                        if (!in_array($uri, $this->visitedPages) && !in_array($uri, $this->pages)) {
                            $this->pages[] = $uri;
                        }
                    }

                    $targetDomain = parse_url($link->getUri(), PHP_URL_HOST);
                    if ($targetDomain !== $mainDomain && preg_match('/https?:\/\/(www.)?[^.]+\.[a-zA-Z]{2,3}(\.[a-zA-Z]{2,3})*(\/.+)*/', $link->getUri())) {
                        if (!in_array($link->getUri(), $this->links)) {
                            $this->links[] = $link->getUri();
                            fwrite($file, $link->getUri()."\n");
                        }
                    }
                }

                $io->table(
                    ['Total pages', 'Visited pages', 'Collected links'],
                    [[count($this->pages), count($this->visitedPages), count($this->links)]]
                );

                ++$i;
            } while (count($this->pages) > count($this->visitedPages));
        } catch (ProxyNotFoundException $e) {
            $this->logger->warning(sprintf('Proxy Not Found, skip fetch (%s)', $address));
            //FIXME: what we must do?
        } catch (DataNotFoundException $e) {
            $this->logger->error(sprintf('Data is not exists for '), [$e->getTraceAsString()]);
        } catch (ConnectException $e) {
            $this->logger->error(sprintf('Connection Error: We can not fetch data for %s (%s)', get_class($e), $e->getMessage()), [$e->getTraceAsString()]);
            $this->logger->info("\t------> Select another proxy...");
//            $this->proxyManager->getProxy(false, true);
        } catch (\Exception $e/*TODO: create appropriate class for crawler exceptions*/) {
            $this->logger->error(sprintf('We can not fetch data for %s (%s)', get_class($e), $e->getMessage()), [$e->getTraceAsString()]);
        }
    }
}
