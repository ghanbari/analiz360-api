<?php

namespace App\Crawler;

use App\Exception\Crawler\DataNotFoundException;
use App\Proxy\Proxy;
use App\Exception\Proxy\ProxyNotFoundException;
use App\Proxy\ProxyManager;
use Goutte\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class Crawler
{
    /**
     * crawl without proxy.
     */
    const WITHOUT_PROXY = 'WITHOUT_PROXY';

    /**
     * crawl with proxy.
     */
    const WITH_PROXY = 'WITH_PROXY';

    /**
     * crawl with proxy, for  403 request try with other proxy.
     */
    const FORCE_PROXY = 'FORCE_PROXY';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ProxyManager
     */
    private $proxyManager;

    /**
     * @var int
     */
    private $crawledCount;

    /**
     * @var string
     */
    protected $proxyStatus = self::WITH_PROXY;

    /**
     * @var array
     */
    protected $proxyProtocols = [];

    /**
     * @var int Crawler timeout, default value is 20 second
     */
    private $timeout;

    public function __construct(LoggerInterface $logger, ProxyManager $proxyManager = null, $followRedirect = false)
    {
        $this->client = new Client();
        $this->client->followRedirects($followRedirect);
        $this->client->setServerParameter('HTTP_ACCEPT', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8');
        $this->client->setServerParameter('HTTP_ACCEPT_ENCODING', 'gzip, deflate');
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'en-US,en;q=0.9,fa;q=0.8,und;q=0.7');
        $this->logger = $logger;
        $this->crawledCount = 0;
        $this->proxyManager = $proxyManager;
        $this->timeout = 20;
    }

    /**
     * @param $proxyStatus
     */
    public function setProxyStatus($proxyStatus): void
    {
        $this->logger->info('Set proxy status {proxyStatus}', ['proxyStatus' => $proxyStatus]);
        $this->proxyStatus = $proxyStatus;
    }

    /**
     * @param array $proxyProtocols
     */
    public function setProxyProtocols(array $proxyProtocols): void
    {
        $this->logger->info('Set proxy protocol', ['protocols' => $proxyProtocols]);
        $this->proxyProtocols = $proxyProtocols;
    }

    public function getCrawledCount()
    {
        return $this->crawledCount;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    protected function getKeyword($uri)
    {
        return parse_url($uri, PHP_URL_HOST).'+'.parse_url($uri, PHP_URL_PATH);
    }

    protected function getRandomUserAgent()
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
            'Mozilla/5.0 (Windows NT 5.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.2; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 4.4.2; XMP-6250 Build/HAWK) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Safari/537.36 ADAPI/2.0 (UUID:9e7df0ed-2a5c-4a19-bec7-2cc54800f99d) RK3188-ADAPI/1.2.84.533 (MODEL:XMP-6250)',
            'Mozilla/5.0 (Linux; Android 6.0.1; SM-G532G Build/MMB29T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.83 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 6.0; vivo 1713 Build/MRA58K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.124 Mobile Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.89 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 7.1; Mi A1 Build/N2G47H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.83 Mobile Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.67 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:64.0) Gecko/20100101 Firefox/64.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Firefox/58.0.1',
            'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.13; ko; rv:1.9.1b2) Gecko/20081201 Firefox/60.0',
            'Opera/9.80 (Windows NT 6.1; WOW64) Presto/2.12.388 Version/12.18',
            'Opera/9.80 (Windows NT 5.1; WOW64) Presto/2.12.388 Version/12.17',
            'Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.18',
        ];

        $key = array_rand($userAgents, 1);

        return $userAgents[$key];
    }

    protected function getRandomReferer($keyword)
    {
        $referers = [
            'https://www.google.com/search?source=hp&ei=ThP4W-jzNIe0sQG4j6DoCA&q='
            .$keyword.'&btnK=%D8%AC%D8%B3%D8%AA%D8%AC%D9%88%DB%8C+Google&oq='.$keyword,
            'https://de.search.yahoo.com/search?p='.$keyword.'&fr=yfp-t&fp=1&toggle=1&cop=mss&ei=UTF-8',
            'https://www.bing.com/search?q='
            .$keyword.'&form=PRDEDE&httpsmsn=1&refig=f1762ec6e6234eefa9cc5c775f32eb1b&sp=-1&pq='.$keyword,
            'https://suche.aol.de/aol/search?s_chn=prt_bon&q='.$keyword.'&s_it=aolde-homePage50&s_chn=hp&rp=&s_qt=',
            'https://www.baidu.com/s?ie=utf-8&f=3&rsv_bp=0&rsv_idx=1&tn=baidu&wd='.$keyword,
            'https://www.wolframalpha.com/input/?i='.$keyword,
        ];

        $key = array_rand($referers, 1);

        return $referers[$key];
    }

    protected function getRandomIp()
    {
        $ips = [
            '2.178.', '5.120.', '91.98.', '94.74.', '95.82.', '5.52.',
            '5.232.', '89.219.', '89.196.', '5.160.', '86.105.',
            '5.238.', '46.224.', '151.238.', '79.127.', '31.57.', '87.107.',
            '164.215.', '188.159.', '31.56.',
        ];

        $key = array_rand($ips, 1);
        $ip = $ips[$key];

        return $ip.mt_rand(10, 190).'.'.mt_rand(10, 190);
    }

    /**
     * @param string $uri
     * @param bool   $newInstance
     *
     * @return array
     */
    protected function getCrawler(string $uri, bool $newInstance = false)
    {
        /** @var DomCrawler */
        static $crawler;
        static $status;
        /** @var Proxy $proxy */
        static $proxy;
        $config = [
            'timeout' => $this->timeout,
        ];

        // if follow redirect, then $crawler->getUri() may not equal $uri
        if ($newInstance || is_null($crawler) || $crawler->getUri() !== $uri) {
            if (self::WITHOUT_PROXY !== $this->proxyStatus && !$this->proxyManager instanceof ProxyManager) {
                throw new \InvalidArgumentException('Proxy Manager service is not set');
            }

            ++$this->crawledCount;
            $this->client->setServerParameter('HTTP_USER_AGENT', $this->getRandomUserAgent());
            $this->client->setServerParameter('HTTP_REFERER', $this->getRandomReferer($this->getKeyword($uri)));
            $this->client->setServerParameter('HTTP_X_Forwarded_For', $this->getRandomIp());

            $this->client->getHistory()->clear();

            $checkProxy = function ($new = false, $forceCheck = false) use (&$proxy, &$config) {
                if (self::WITH_PROXY === $this->proxyStatus || self::FORCE_PROXY === $this->proxyStatus) {
                    $proxy = $this->proxyManager->getProxy($forceCheck, $new, $this->proxyProtocols);
                    if (is_null($proxy)) {
                        throw new ProxyNotFoundException();
                    }

                    $proxy->setData(array_merge($proxy->getData(), ['requestErrorCount' => 0]));

                    $this->logger->info('Set Proxy {proxy} for {crawler} crawler', ['proxy' => $proxy, 'crawler' => get_class($this)]);
                    if (!empty($this->proxyProtocols)) {
                        $config['protocols'] = $this->proxyProtocols;
                    }
                    $config['proxy'] = [
                        $proxy->getProtocol() => $proxy->getIp().':'.$proxy->getPort(),
                    ];
                }
            };

            $retry = 12;
            do {
                if (--$retry < 0) {
                    throw new DataNotFoundException();
                }

                $crawler = null;
                try {
                    $checkProxy();
                    $this->client->setClient(new \GuzzleHttp\Client($config));
                    $crawler = $this->client->request('get', $uri);
                } catch (ConnectException $e) {
                    $this->logger->warning(
                        'CrawlerConnectException: We can not fetch data, maybe proxy have problem!?',
                        [
                            'repository' => 'sslProxy',
                            'error.message' => $e->getMessage(),
                            'error.stack' => $e->getTrace(),
                            'error.kind' => get_class($e),
                        ]
                    );

                    if (self::WITHOUT_PROXY === $this->proxyStatus) {
                        throw $e;
                    }

                    $checkProxy(true);
                } catch (RequestException $e) {
                    $this->logger->warning(
                        'CrawlerRequestException: We can not fetch data of {uri}',
                        [
                            'uri' => $uri,
                            'crawler' => get_class($this),
                            'response' => $e->getResponse(),
                            'proxy_status' => $this->proxyStatus,
                            'error.code' => $e->getCode(),
                            'error.message' => $e->getMessage(),
                            'error.stack' => $e->getTrace(),
                            'error.kind' => get_class($e),
                        ]
                    );

                    if (self::WITHOUT_PROXY === $this->proxyStatus) {
                        throw $e;
                    }

                    $proxyData = $proxy->getData();
                    $proxyData['requestErrorCount'] = isset($proxyData['requestErrorCount']) ? $proxyData['requestErrorCount'] + 1 : 1;
                    $proxy->setData($proxyData);
                    $new = $proxyData['requestErrorCount'] > 5;
                    $checkProxy($new, !$new);
                }
            } while (self::WITHOUT_PROXY !== $this->proxyStatus && (is_null($crawler) || !$crawler->count() || !$this->client->getResponse()));

            $status = $this->client->getResponse()->getStatus();
            $this->logger->info(
                'Create crawler & fetch data of {uri} ({status})',
                ['uri' => $uri, 'status' => $status, 'crawler' => get_class($this)]
            );

            if (in_array($status, [403, 503, 502]) && self::FORCE_PROXY === $this->proxyStatus) {
                $this->logger->notice(
                    'Blocked IP. try another proxy',
                    ['uri' => $uri, 'status' => $status, 'crawler' => get_class($this)]
                );
                $proxy = $this->proxyManager->getProxy(false, true);

                return self::getCrawler($uri, true);
            }

            return [$crawler, $status];
        }

        return [$crawler, $status];
    }
}
