<?php

namespace App\Crawler;

use App\Crawler\Crawler as BaseCrawler;
use App\Exception\Crawler\DataNotFoundException;
use App\Proxy\ProxyManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class WebsiteCrawler extends BaseCrawler
{
    public function __construct(LoggerInterface $crawlerLogger, ProxyManager $proxyManager)
    {
        parent::__construct($crawlerLogger, $proxyManager);
    }

    /**
     * @param $uri
     * @param bool $newInstance
     *
     * @return Crawler
     */
    public function getCrawler($uri, $newInstance = false)
    {
        $res = parent::getCrawler($uri, $newInstance);

        if (200 == !$res[1]) {
            $this->logger->warning(sprintf('Response code is not expected(%s)', $res[1]), [$this->client->getHistory(), $this->client->getResponse()]);
        }

        if (301 == $res[1] || 302 == $res[1]) {
            throw new DataNotFoundException();
        }

        return $res[0];
    }
}
