<?php

namespace App\Crawler;

use App\Crawler\Crawler as BaseCrawler;
use App\Exception\Crawler\DataNotFoundException;
use App\Proxy\ProxyManager;
use App\Repository\CountryRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class AlexaCrawler extends BaseCrawler
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
            $data = $crawler->filterXPath('//*[@id="visitorPercentage"]')->html();
            $data = json_decode($data, true);
            $countriesInfo = [];
            foreach ($data as $country) {
                $countriesInfo[$country['name']] = $country;
            }

            $crawler->filterXPath('//*[@id="countrydropdown"]/ul/li')
                ->each(function (Crawler $node, $i) use (&$geogpraphies, $flattenResponse, $countriesInfo) {
                    if (!$node->attr('data-value')) {
                        return;
                    }

                    $info = explode('#', $node->text());
                    $countryName = trim(preg_replace('/[^a-zA-Z\s]/', '', $info[0]));

                    $geo['country'] = $this->countryRepository->findCountryId(strtoupper($countriesInfo[$countryName]['code']));
                    $geo['visitorsPercent'] = $countriesInfo[$countryName]['visitors_percent'];
                    $geo['pageViewsPerUser'] = $countriesInfo[$countryName]['pageviews_per_user'];
                    $geo['pageViewsPercent'] = $countriesInfo[$countryName]['pageviews_percent'];
                    $geo['rank'] = intval(str_replace(',', '', $info[1]));

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
     *
     * @deprecated Remove in future(alexa related domains dont have very accurate)
     */
    public function getRelatedDomains($domain): array
    {
        $relatedDomains = [];
        $crawler = $this->getCrawler($domain);
        try {
            $crawler->filterXPath('//*[@id="card_overlap"]/div/section[2]/div[3]/section/div[2]/div')->each(function (Crawler $node, $i) use (&$relatedDomains) {
                $related = [];
                $related['domain'] = trim($node->filterXPath('//*/div[1]')->attr('data-popsicle'));
                $related['score'] = trim($node->filterXPath('//*/div[2]')->attr('data-popsicle'));
                $relatedDomains[] = $related;
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
            $crawler->filterXPath('//*[@id="card_mini_topkw"]/section[2]/div[2]/div')->each(function (Crawler $node, $i) use (&$keywords) {
                $keyword['keyword'] = trim($node->filterXPath('//*/div[1]/span')->text());
                $keyword['percent'] = trim($node->filterXPath('//*/div[2]/span')->text(), '%');
                $keyword['percent'] = is_numeric($keyword['percent']) ? $keyword['percent'] : null;

                $keyword['sharePercent'] = trim($node->filterXPath('//*/div[3]/span')->text(), '%');
                $keyword['sharePercent'] = is_numeric($keyword['sharePercent']) ? $keyword['sharePercent'] : null;

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

    public function getUpstreams($domain)
    {
        $upstreams = [];
        $crawler = $this->getCrawler($domain);
        try {
            $crawler->filterXPath('//*[@id="card_metrics"]/section[4]/div[2]/div[1]/p')->each(function (Crawler $node, $i) use (&$upstreams) {
                $upstream['percent'] = trim($node->filterXPath('//*/span')->text(), '%');
                $upstream['percent'] = is_numeric($upstream['percent']) ? $upstream['percent'] : 0;
                $upstream['domain'] = trim(str_replace($node->filterXPath('//*/span')->text(), '', $node->text()));
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

    public function getDownstreams($domain)
    {
        $upstreams = [];
        $crawler = $this->getCrawler($domain);
        try {
            $crawler->filterXPath('//*[@id="card_metrics"]/section[4]/div[2]/div[2]/p')->each(function (Crawler $node, $i) use (&$upstreams) {
                $upstream['percent'] = trim($node->filterXPath('//*/span')->text(), '%');
                $upstream['percent'] = is_numeric($upstream['percent']) ? $upstream['percent'] : 0;
                $upstream['domain'] = trim(str_replace($node->filterXPath('//*/span')->text(), '', $node->text()));
                $upstreams[] = $upstream;
            });
        } catch (\Exception $e) {
            $this->logger->warning(
                'We can not fetch {domain} downstream information.',
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
            $globalRankElement = $crawler->filter('#card_mini_trafficMetrics > div.rankmini-container > div.rankmini-global > div.rankmini-rank');
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
//                    'response' => $crawler->count() ? base64_encode($crawler->html()) : $crawler,
                ]
            );

            throw new DataNotFoundException(sprintf('Data is not exists(%s)', $e->getMessage()), $e->getCode(), $e);
        }
    }

    public function getGlobalRankChange(Crawler $crawler)
    {
        try {
            $globalRank = $crawler->filter('#card_rank > section.rank > div.rank-global span.rank');
            $rank = trim($globalRank->text());

            if (preg_match('/\d+\s(K|M)/i', $rank, $unit)) {
                $rank = floatval($rank);
                $rank *= 'K' === $unit[1] ? 1000 : 1000 * 1000;
            }

            if (is_numeric($rank)) {
                $rank *= strpos($globalRank->attr('class'), 'up') ? 1 : -1;

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
            $engageRate = $crawler->filter('#card_metrics > section.engagement > div.flex > div:nth-child(3) > p.data');
            $engageRate = str_replace('%', '', trim($engageRate->text()));
            $engageRate = explode(' ', $engageRate);

            return is_array($engageRate) ? (floatval($engageRate[0]) ?: null) : null;
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
            $engageRateChange = $crawler->filter('#card_metrics > section.engagement > div.flex > div:nth-child(3) > p.data > span');
            $change = str_replace('%', '', trim($engageRateChange->text()));
            if (is_numeric($change)) {
                $change *= strpos($engageRateChange->attr('class'), 'up') ? 1 : -1;

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
            $dailyPageView = $crawler->filter('#card_metrics > section.engagement > div.flex > div:nth-child(1) > p.data');
            $dailyPageView = trim($dailyPageView->text());
            $dailyPageView = explode(' ', $dailyPageView);

            return is_array($dailyPageView) ? (floatval($dailyPageView[0]) ?: null) : null;
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
            $dailyPageViewChange = $crawler->filter('#card_metrics > section.engagement > div.flex > div:nth-child(1) > p.data > span');
            $change = str_replace('%', '', trim($dailyPageViewChange->text()));
            if (is_numeric($change)) {
                $change *= strpos($dailyPageViewChange->attr('class'), 'down') ? -1 : 1;

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
            $dailyTimeOnSite = $crawler->filter('#card_metrics > section.engagement > div.flex > div:nth-child(2) > p.data');
            $dailyTimeOnSite = trim($dailyTimeOnSite->text());
            $dailyTimeOnSite = explode(' ', $dailyTimeOnSite);
            $dailyTimeOnSite = is_array($dailyTimeOnSite) ? $dailyTimeOnSite[0] : '';

            $time = explode(':', $dailyTimeOnSite);

            if (count($time) > 1) {
                return is_array($time) ? $time[0] * 60 + $time[1] : null;
            }
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
            $dailyTimeOnSiteChange = $crawler->filter('#card_metrics > section.engagement > div.flex > div:nth-child(2) > p.data > span');
            $change = str_replace('%', '', trim($dailyTimeOnSiteChange->text()));
            if (is_numeric($change)) {
                $change *= strpos($dailyTimeOnSiteChange->attr('class'), 'down') ? -1 : 1;

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
            $searchVisit = $crawler->filter('#card_metrics > section.sources > div.flex.centered > div:nth-child(1) > div.referral-social');
            $searchVisit = $searchVisit->attr('data-referral');

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
        // removed in new version of alexa
        return null;

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
            $totalSiteLinking = $crawler->filter('#card_metrics > section.linksin span.data');
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
        // removed in new version of alexa
        return null;

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
