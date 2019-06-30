<?php

namespace App\Crawler;

use App\Crawler\Crawler as BaseCrawler;
use App\Exception\Crawler\DataNotFoundException;
use App\Proxy\ProxyManager;
use App\Repository\CountryRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class AlexaCrawlerOld extends BaseCrawler
{
    /**
     * @var CountryRepository
     */
    private $countryRepository;

    /**
     * AlexaCrawler constructor.
     *
     * @param CountryRepository $countryRepository
     * @param LoggerInterface   $crawlerLogger
     * @param ProxyManager      $proxyManager
     */
    public function __construct(CountryRepository $countryRepository, LoggerInterface $crawlerLogger, ProxyManager $proxyManager)
    {
        parent::__construct($crawlerLogger, $proxyManager);
        $this->countryRepository = $countryRepository;
        $this->setProxyStatus(self::FORCE_PROXY);
        $this->setProxyProtocols([ProxyManager::PROXY_PROTOCOL_HTTPS]);
    }

    /**
     * @param $uri
     *
     * @return string
     */
    protected function getKeyword($uri)
    {
        return 'alexa+'.substr($uri, strpos($uri, '/') + 1, -1);
    }

    /**
     * @param $domain
     * @param bool $newInstance
     *
     * @return array
     */
    protected function getCrawler($domain, $newInstance = false)
    {
        $res = parent::getCrawler('https://www.alexa.com/siteinfo/'.$domain.'?ver=classic', $newInstance);

        if (200 == !$res[1]) {
            $this->logger->warning(
                'Response code is not expected({code})',
                ['code' => $res[1], 'history' => $this->client->getHistory(), 'response' => $this->client->getResponse()]
            );
        }

        if (301 == $res[1] || 302 == $res[1]) {
            throw new DataNotFoundException();
        }

        return $res[0];
    }

    /**
     * @param $domain
     *
     * @return array
     */
    public function getBasicInfo($domain): array
    {
        $data = [];
        $crawler = $this->getCrawler($domain);

        $data['globalRank'] = $this->getGlobalRank($crawler);
        $data['globalRankChange'] = $this->getGlobalRankChange($crawler);
        $data['engageRate'] = $this->getEngageRate($crawler);
        $data['engageRateChange'] = $this->getEngageRateChange($crawler);
        $data['dailyPageView'] = $this->getDailyPageView($crawler);
        $data['dailyPageViewChange'] = $this->getDailyPageViewChange($crawler);
        $data['dailyTimeOnSite'] = $this->getDailyTimeOnSite($crawler);
        $data['dailyTimeOnSiteChange'] = $this->getDailyTimeOnSiteChange($crawler);
        $data['searchVisit'] = $this->getSearchVisit($crawler);
        $data['searchVisitChange'] = $this->getSearchVisitChange($crawler);
        $data['totalSiteLinking'] = $this->getTotalSiteLinking($crawler);
        $data['description'] = $this->getDescription($crawler);
        $data['loadSpeed'] = $this->getLoadSpeed($crawler);

        return $data;
    }

    /**
     * @param $domain
     * @param $flattenResponse
     *
     * @return array
     */
    public function getGeographies($domain, $flattenResponse): array
    {
        $geogpraphies = [];
        $crawler = $this->getCrawler($domain);
        try {
            $crawler->filterXPath('//*[@id="demographics_div_country_table"]/tbody/tr')
                ->each(function (Crawler $node, $i) use (&$geogpraphies, $flattenResponse) {
                    $countryLink = explode('/', $node->filterXPath('//*/td[1]/a')->attr('href'));
                    $geo['country'] = array_pop($countryLink);
                    $geo['percent'] = trim($node->filterXPath('//*/td[2]/span')->text(), '%');
                    $geo['rank'] = preg_replace('/[^\d]/', '', $node->filterXPath('//*/td[3]/span')->text());

                    $geo['percent'] = is_numeric($geo['percent']) ? $geo['percent'] : null;
                    $geo['rank'] = is_numeric($geo['rank']) ? $geo['rank'] : null;

                    $geo['country'] = $this->countryRepository->findCountryId(strtoupper($geo['country']));
                    if (!is_null($geo['country'])) {
                        if ($flattenResponse and is_object($geo['country'])) {
                            $geo['country'] = $geo['country']->getName();
                        }
                        $geogpraphies[] = $geo;
                    } else {
                        $this->logger->warning('Country is not found', ['country' => $geo['country']]);
                    }
                });
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch {domain} geography information.',
                [
                    'domain' => $domain,
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }

        return $geogpraphies;
    }

    /**
     * @param $domain
     *
     * @return array
     */
    public function getRelatedDomains($domain): array
    {
        $relatedDomains = [];
        $crawler = $this->getCrawler($domain);
        try {
            $crawler->filterXPath('//*[@id="audience_overlap_table"]/tbody/tr')->each(function (Crawler $node, $i) use (&$relatedDomains) {
                $relatedDomains[]['domain'] = trim($node->filterXPath('//*/td/a')->text());
            });
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch {domain} related information.',
                [
                    'domain' => $domain,
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }

        return $relatedDomains;
    }

    public function getKeywords($domain)
    {
        $keywords = [];
        $crawler = $this->getCrawler($domain);
        try {
            $crawler->filterXPath('//*[@id="keywords_top_keywords_table"]/tbody/tr')->each(function (Crawler $node, $i) use (&$keywords) {
                $keyword['keyword'] = trim($node->filterXPath('//*/td[1]/span[2]')->text());
                $keyword['percent'] = trim($node->filterXPath('//*/td[2]/span')->text(), '%');
                $keyword['percent'] = is_numeric($keyword['percent']) ? $keyword['percent'] : null;

                $keywords[] = $keyword;
            });
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch {domain} keyword information.',
                [
                    'domain' => $domain,
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }

        return $keywords;
    }

    public function getupstreams($domain)
    {
        $upstreams = [];
        $crawler = $this->getCrawler($domain);
        try {
            $crawler->filterXPath('//*[@id="keywords_upstream_site_table"]/tbody/tr')->each(function (Crawler $node, $i) use (&$upstreams) {
                $upstream['domain'] = trim($node->filterXPath('//*/td[1]/a')->text());
                $upstream['percent'] = trim($node->filterXPath('//*/td[2]/span')->text(), '%');
                $upstream['percent'] = is_numeric($upstream['percent']) ? $upstream['percent'] : 0;
                $upstreams[] = $upstream;
            });
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch {domain} upstream information.',
                [
                    'domain' => $domain,
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }

        return $upstreams;
    }

    public function getBacklinks($domain)
    {
        $backlinks = [];
        $crawler = $this->getCrawler($domain);
        try {
            $crawler->filterXPath('//*[@id="linksin_table"]/tbody/tr')->each(function (Crawler $node, $i) use (&$backlinks) {
                $backlink['rank'] = $i;
                $backlink['domain'] = trim($node->filterXPath('//*/td[2]/span/a')->text());
                $backlink['page'] = $node->filterXPath('//*/td[3]/span/a')->attr('href');
                $backlinks[] = $backlink;
            });
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch {domain} back link information.',
                [
                    'domain' => $domain,
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }

        return $backlinks;
    }

    public function getToppages($domain)
    {
        $toppages = [];
        $crawler = $this->getCrawler($domain);
        try {
            $crawler->filterXPath('//*[@id="subdomain_table"]/tbody/tr')->each(function (Crawler $node, $i) use (&$toppages) {
                $toppage['address'] = trim($node->filterXPath('//*//td[1]/span')->text());
                $toppage['percent'] = trim($node->filterXPath('//*/td[2]/span')->text(), '%');
                $toppage['percent'] = is_numeric($toppage['percent']) ? $toppage['percent'] : null;
                $toppages[] = $toppage;
            });
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch {domain} top page information.',
                [
                    'domain' => $domain,
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }

        return $toppages;
    }

    public function getGlobalRank(Crawler $crawler)
    {
        try {
            $globalRankElement = $crawler->filterXPath('//*[@id="traffic-rank-content"]/div/span[2]/div[1]/span/span/div/strong');
            $globalRank = preg_replace('/[^\d]/', '', $globalRankElement->text());

            if (empty($globalRank)) {
                throw new DataNotFoundException('Data is not exists');
            }

            return $globalRank;
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch global rank information.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );

            throw new DataNotFoundException(sprintf('Data is not exists(%s)', $e->getMessage()), $e->getCode(), $e);
        }
    }

    public function getGlobalRankChange(Crawler $crawler)
    {
        try {
            $globalRank = $crawler->filterXPath('//*[@id="traffic-rank-content"]/div/span[2]/div[1]/span/span/div/span');
            $rank = trim($globalRank->text());
            if (is_numeric($rank)) {
                $rank *= strpos($globalRank->attr('class'), 'change-down') ? 1 : -1;

                return $rank;
            }
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch global rank change information.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }
    }

    public function getEngageRate(Crawler $crawler)
    {
        try {
            $engageRate = $crawler->filterXPath('//*[@id="engagement-content"]/span[1]/span/span/div/strong');
            $engageRate = str_replace('%', '', trim($engageRate->text()));

            return is_numeric($engageRate) ? $engageRate : null;
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch engage rate information.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }
    }

    public function getEngageRateChange(Crawler $crawler)
    {
        try {
            $engageRateChange = $crawler->filterXPath('//*[@id="engagement-content"]/span[1]/span/span/div/span');
            $change = str_replace('%', '', trim($engageRateChange->text()));
            if (is_numeric($change)) {
                $change *= strpos($engageRateChange->attr('class'), 'change-down') ? 1 : -1;

                return $change;
            }
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch engage rate change information.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }
    }

    public function getDailyPageView(Crawler $crawler)
    {
        try {
            $dailyPageView = $crawler->filterXPath('//*[@id="engagement-content"]/span[2]/span/span/div/strong');
            $dailyPageView = trim($dailyPageView->text());

            return is_numeric($dailyPageView) ? $dailyPageView : null;
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch daily page view information.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }
    }

    public function getDailyPageViewChange(Crawler $crawler)
    {
        try {
            $dailyPageViewChange = $crawler->filterXPath('//*[@id="engagement-content"]/span[2]/span/span/div/span');
            $change = str_replace('%', '', trim($dailyPageViewChange->text()));
            if (is_numeric($change)) {
                $change *= strpos($dailyPageViewChange->attr('class'), 'change-down') ? -1 : 1;

                return $change;
            }
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch daily page view change information.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }
    }

    public function getDailyTimeOnSite(Crawler $crawler)
    {
        try {
            $dailyTimeOnSite = $crawler->filterXPath('//*[@id="engagement-content"]/span[3]/span/span/div/strong');
            $time = explode(':', trim($dailyTimeOnSite->text()));

            return is_array($time) ? $time[0] * 60 + $time[1] : null;
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch "daily time on site" information.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }
    }

    public function getDailyTimeOnSiteChange(Crawler $crawler)
    {
        try {
            $dailyTimeOnSiteChange = $crawler->filterXPath('//*[@id="engagement-content"]/span[3]/span/span/div/span');
            $change = str_replace('%', '', trim($dailyTimeOnSiteChange->text()));
            if (is_numeric($change)) {
                $change *= strpos($dailyTimeOnSiteChange->attr('class'), 'change-down') ? -1 : 1;

                return $change;
            }
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch "daily time on site change" information.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }
    }

    public function getSearchVisit(Crawler $crawler)
    {
        try {
            $searchVisit = $crawler->filterXPath('//*[@id="keyword-content"]/span[1]/span/span/div/strong');
            $searchVisit = str_replace('%', '', trim($searchVisit->text()));

            return is_numeric($searchVisit) ? $searchVisit : null;
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch "search visit" information.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }
    }

    public function getSearchVisitChange(Crawler $crawler)
    {
        try {
            $searchVisitChange = $crawler->filterXPath('//*[@id="keyword-content"]/span[1]/span/span/div/span');
            $change = str_replace('%', '', trim($searchVisitChange->text()));
            if (is_numeric($change)) {
                $change *= strpos($searchVisitChange->attr('class'), 'change-down') ? -1 : 1;

                return $change;
            }
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch "search visit change" information.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }
    }

    public function getTotalSiteLinking(Crawler $crawler)
    {
        try {
            $totalSiteLinking = $crawler->filterXPath('//*[@id="linksin-panel-content"]/div[1]/span/div/span');
            $totalSiteLinking = preg_replace('/[^\d]/', '', $totalSiteLinking->text());

            return is_numeric($totalSiteLinking) ? $totalSiteLinking : null;
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch "total site linking" information.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }
    }

    public function getDescription(Crawler $crawler)
    {
        try {
            $description = $crawler->filterXPath('//*[@id="contact-panel-content"]/div[2]/span[1]/p[1]');

            return $description->text();
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch description information.',
                [
                    'error.message' => $e->getMessage(),
                    'error.stack' => $e->getTrace(),
                    'error.kind' => get_class($e),
                ]
            );
        }
    }

    public function getLoadSpeed(Crawler $crawler)
    {
        $loadSpeed = $crawler->filterXPath('//*[@id="loadspeed-panel-content"]/p/span');

        return !empty($loadSpeed->evaluate('//*[@id="loadspeed-panel-content"]/p/span')) ? $loadSpeed->text() : null;
    }
}
