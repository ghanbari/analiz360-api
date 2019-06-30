<?php

namespace App\Crawler;

use App\Crawler\Crawler as BaseCrawler;
use App\Proxy\Proxy;
use App\Proxy\ProxyRepositoryInterface;
use Goutte\Client;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class FreeProxyCrawler extends BaseCrawler implements ProxyRepositoryInterface
{
    /**
     * FreeProxyCrawler constructor.
     *
     * @param LoggerInterface $proxyLogger
     */
    public function __construct(LoggerInterface $proxyLogger)
    {
        parent::__construct($proxyLogger);
        $this->client = new Client();
        $this->setProxyStatus(self::WITHOUT_PROXY);
    }

    /**
     * @return array
     */
    public function getProxies()
    {
        $proxies = [];
        try {
            $crawler = $this->getCrawler('https://free-proxy-list.net/', true);
        } catch (ConnectException $e) {
            $this->logger->error(
                'We can not connect to "https://free-proxy-list.net", please check your connection',
                [
                    'repository' => 'free-proxy',
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );

            return [];
        }

        if (200 == $crawler[1]) {
            $crawler[0]->filterXPath('//*[@id="proxylisttable"]/tbody/tr')->each(function (Crawler $row, $i) use (&$proxies) {
                try {
                    $proxy = new Proxy();
                    $proxy->setIp($row->filterXPath('//*/td[1]')->text());
                    $proxy->setPort($row->filterXPath('//*/td[2]')->text());
                    $isHttps = $row->filterXPath('//*/td[7]')->text();
                    $protocol = 'yes' === $isHttps ? 'https' : 'http';
                    $proxy->setProtocol($protocol);
                    $proxies[] = $proxy;
                } catch (\Exception $e) {
                    $this->logger->error(
                        'We can not fetch proxy from {repository} list.',
                        [
                            'repository' => 'freeProxy',
                            'error.message' => $e->getMessage(),
                            'error.stack' => $e->getTrace(),
                            'error.kind' => get_class($e),
                        ]
                    );
                }
            });
        }

        $this->logger->info('Fetch {count} proxy from free-proxy-list.net', ['count' => count($proxies)]);

        return $proxies;
    }
}
