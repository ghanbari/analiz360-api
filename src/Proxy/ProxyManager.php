<?php

namespace App\Proxy;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class ProxyManager
{
    const PROXY_PROTOCOL_HTTP = 'http';
    const PROXY_PROTOCOL_HTTPS = 'https';

    private const TIMEOUT = 5;

    /**
     * @var Proxy[]
     */
    private $proxies = [];

    /**
     * @var Proxy
     */
    private $currentProxy;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var iterable
     */
    private $proxyRepositories;

    public function __construct(iterable $proxyRepositories, LoggerInterface $proxyLogger)
    {
        $this->logger = $proxyLogger;
        $this->proxyRepositories = $proxyRepositories;
    }

    public function getProxy($forceCheck = false, $forceSkip = false, $protocols = [self::PROXY_PROTOCOL_HTTP, self::PROXY_PROTOCOL_HTTPS]): ?Proxy
    {
        if (is_null($this->currentProxy) || $forceSkip || (!empty($protocols) && !in_array($this->currentProxy->getProtocol(), $protocols))) {
            if (!is_null($this->currentProxy) && $forceSkip) {
                $this->logger->info('Ignore current proxy: {proxy}', ['proxy' => $this->currentProxy]);
                $this->currentProxy = null;
            }

            if (empty($this->proxies)) {
                $this->fetchProxies();
            }

            $this->currentProxy = $this->getOneProxy($protocols);
        }

        if ($forceCheck && !is_null($this->currentProxy)) {
            if ($this->doesWork($this->currentProxy)) {
                return $this->currentProxy;
            } else {
                $this->currentProxy = $this->getOneProxy($protocols);
            }
        }

        return $this->currentProxy;
    }

    private function fetchProxies()
    {
        $this->logger->info('Fetch Proxies list from site.');
        foreach ($this->proxyRepositories as $proxyRepository) {
            try {
                $this->proxies = array_merge($this->proxies, $proxyRepository->getProxies());
            } catch (\Exception $e) {
                $this->logger->error(
                    'Can not fetch {repository} proxies.',
                    [
                        'repository' => get_class($proxyRepository),
                        'error.message' => $e->getMessage(),
                        'error.stack' => $e->getTrace(),
                        'error.kind' => get_class($e),
                    ]
                );
            }
        }
        shuffle($this->proxies);
    }

    private function getOneProxy(array $protocols = [])
    {
        $doesWork = false;
        do {
            $proxy = array_shift($this->proxies);
            if (is_null($proxy)) {
                $this->fetchProxies();
                continue;
            }

            if (!empty($protocols) && !in_array($proxy->getProtocol(), $protocols)) {
                continue;
            }

            $doesWork = $this->doesWork($proxy);
        } while (!$doesWork);

        return $proxy;
    }

    private function doesWork(Proxy $proxy): bool
    {
        $this->logger->info('Check {proxy} proxy ...', ['proxy' => $proxy]);
        $config = [
            'timeout' => self::TIMEOUT,
            'proxy' => [
                $proxy->getProtocol() => $proxy->getIp().':'.$proxy->getPort(),
            ],
        ];

        try {
            $client = new Client($config);
            $response = $client->request('get', 'https://ifconfig.co/', ['headers' => ['Accept' => 'application/json']]);
            if (200 == $response->getStatusCode()) {
                $data = $response->getBody()->getContents();
                if (!empty($data)) {
                    $result = json_decode($data, true);
                    if ($result['ip'] == $proxy->getIp()) {
                        $this->logger->info('Proxy {proxy} connected successfully.', ['proxy' => $proxy]);

                        return true;
                    } else {
                        $this->logger->warning('{proxy} proxy connection failed.', ['proxy' => $proxy]);
                    }
                }
            }
        } catch (ConnectException | RequestException $e) {
            $this->logger->error(
                'Proxy connection failed: can not connect by {proxy} to "https://ifconfig.co/"',
                [
                    'proxy' => $proxy,
                    'response' => $e->getResponse(),
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }

        return false;
    }
}
