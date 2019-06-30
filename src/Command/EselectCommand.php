<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Goutte\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class EselectCommand extends Command
{
    protected static $defaultName = 'eselect:category';

    private $basePath = 'https://eselect.ir/category';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * EselectCommand constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        parent::__construct(self::$defaultName);
        $this->entityManager = $entityManager;
        $this->client = new Client();
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('starting...');
        $this->logger->info('Fetch root categories');
        $crawler = $this->client->request('get', $this->basePath);

        $crawler->filter('#t3-content > div.namamir > ul > li > a')->each(function (Crawler $node) use ($crawler) {
            $category = $this->entityManager->getRepository('App:Category')->findOneByTitle(trim($node->first('span')->text()));
            if ($category) {
                $this->logger->warning(sprintf('Skip category: %s', $node->first('span')->text()));

                return;
            }

            try {
                $rootCategory = new Category(trim($node->first('span')->text()));
                $this->entityManager->persist($rootCategory);

                $this->getSubCategories($crawler, $node->attr('href'), $rootCategory);
            } catch (\Exception $e) {
                $this->logger->critical(sprintf('%s: %s', get_class($e), $e->getMessage()), [$e->getTraceAsString()]);
            }
            $this->entityManager->flush();
        });
    }

    private function getSubCategories(Crawler $crawler, $baseSelector, Category $parentCategory)
    {
        $this->logger->info(sprintf('Fetch sub categories of %s', $parentCategory->getTitle()));
        $crawler->filter($baseSelector.' a')->each(function (Crawler $link) use ($crawler, $parentCategory) {
            try {
                $subCategory = new Category(trim($link->text()), $parentCategory);
                $this->entityManager->persist($subCategory);

                $this->getWebsitesOfCategory($subCategory, $link->link()->getUri());
            } catch (\Exception $e) {
                $this->logger->critical(sprintf('%s: %s', get_class($e), $e->getMessage()), [$e->getTraceAsString()]);
            }
        });
    }

    private function getWebsitesOfCategory(Category $subCategory, string $uri)
    {
        $this->logger->info(sprintf("Fetch %s category's domains", $subCategory->getTitle()));
        $crawler = $this->client->request('get', $uri);
        $count = $crawler->filter('#t3-content > div.namamir')->count();
        if ($count > 0) {
            $this->logger->info(sprintf('Category %s has sub categories...', $subCategory->getTitle()));
            $this->getSubCategories($crawler, '#t3-content > div.namamir', $subCategory);

            return;
        }

        $urls = $crawler->filter('#t3-content table > tbody tr.text-center td:nth-child(3) a');

        /** @var \DOMElement $url */
        foreach ($urls->getIterator() as $url) {
            try {
                $this->updateSiteData($subCategory, $url->getAttribute('href'));
            } catch (\Exception $e) {
                $this->logger->critical(sprintf('%s: %s', get_class($e), $e->getMessage()), [$e->getTraceAsString()]);
            }
        }
    }

    private function updateSiteData(Category $category, string $uri)
    {
        $this->logger->info(sprintf('Fetch domain %s', $uri));
        $crawler = $this->client->request('get', $uri);
        $address = $crawler->filter('#h1_site_profile > span > div.col.value > a');
        $match = [];
        preg_match('/https?:\/\/(?:www.)?([^\/]+)/', $address->link()->getUri(), $match);
        if (!isset($match[1])) {
            $this->logger->warning(sprintf('Fail to detect domain', $uri));

            return;
        }

        $name = trim($crawler->filter('#h1_site_profile')->text());
        $ssl = $crawler->filter('#rank > div > div:nth-child(7) > div.col.value');
        $provinceName = trim($crawler->filter('#engagement > div > div:nth-child(3) > div.col.value')->text());
        $province = $this->getProvince($provinceName);

        $domain = $this->getDomain($match[1]);
        $domain->setName(substr($name, 0, 255));
        $domain->setCategory($category);
        $domain->setSecure('دارد ' === $ssl);
        if ($province) {
            $domain->setProvince($province);
        }
    }

    /**
     * @param $domainName
     *
     * @return Domain
     */
    private function getDomain($domainName)
    {
        $domain = $this->entityManager->getRepository('App:Domain')->findOneByDomain($domainName);

        if (!$domain) {
            $this->logger->info(sprintf('Add new domain %s', $domainName));
            $domain = new Domain();
            $domain->setName(substr($domainName, 0, 255));
            $domain->setDomain(substr($domainName, 0, 255));
            $domain->setStatus(Domain::STATUS_ACTIVE);
            $this->entityManager->persist($domain);
            $this->entityManager->flush($domain);
        }

        return $domain;
    }

    private function getProvince(string $provinceName)
    {
        return $this->entityManager->getRepository('App:Province')->findOneByName($provinceName);
    }
}
