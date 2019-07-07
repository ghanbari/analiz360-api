<?php

namespace App\Controller;

use App\Api\Dto\DomainStatistics;
use App\Entity\Domain;
use App\Entity\Report;
use App\Repository\DomainWatcherRepository;
use App\Repository\GeographyRepository;
use App\Repository\ReportRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetDomainStatisticsAction extends AbstractController
{
    private $lastReport;

    public function __invoke(Request $request): DomainStatistics
    {
        $dateQuery = $request->query->get('date', []);
        $domainId = $request->attributes->get('id');
        /** @var Domain $domain */
        $domain = $this->getDoctrine()->getRepository('App:Domain')->find($domainId);

        if (!$domain) {
            throw new NotFoundHttpException('Domain is not exists.');
        }

        $secondLastReportDate = $this->getSecondLastReportDate($domain);
        /** @var DomainWatcherRepository $domainWatcherRepo */
        $domainWatcherRepo = $this->getDoctrine()->getRepository('App:DomainWatcher');
        $history = $domainWatcherRepo->getAvailableHistory($domain, $this->getUser());

        $from = new \DateTime(sprintf('-%d days', $history));
        if (isset($dateQuery['after'])) {
            try {
                $after = new \DateTime($dateQuery['after']);
                $from = max($after, $from);
            } catch (\Exception $e) {
                //TODO: log warning
            }
        }

        $till = new \DateTime();
        if (isset($dateQuery['before'])) {
            try {
                $before = new \DateTime($dateQuery['before']);
                $till = min($before, $till);
            } catch (\Exception $e) {
                //TODO: log warning
            }
        }

        $from = $from < $secondLastReportDate ? $from : $secondLastReportDate;
        $from->setTime(0, 0, 0);
        $till->setTime(23, 59, 59);

        $properties = $request->query->get('properties', []);

        $statistic = new DomainStatistics($domain);

        if (empty($properties) or in_array('globalRank', $properties)) {
            $statistic->globalRank = $this->getGlobalRank($domain);
        }

        if (empty($properties) or in_array('globalRanks', $properties)) {
            $statistic->globalRanks = $this->getGlobalRanks($domain, $from, $till);
        }

        if (empty($properties) or in_array('localRank', $properties)) {
            $statistic->localRank = $this->getLocalRank($domain);
        }

        if (empty($properties) or in_array('localRank', $properties)) {
            $statistic->localRanks = $this->getLocalRanks($domain, $from, $till);
        }

        if (empty($properties) or in_array('bounceRates', $properties)) {
            $statistic->bounceRates = $this->getBounceRates($domain, $from, $till);
        }

        if (empty($properties) or in_array('pageViews', $properties)) {
            $statistic->pageViews = $this->getPageViews($domain, $from, $till);
        }

        if (empty($properties) or in_array('timeOnSites', $properties)) {
            $statistic->timeOnSites = $this->getTimeOnSites($domain, $from, $till);
        }

        return $statistic;
    }

    /**
     * @return ReportRepository
     */
    private function getReportRepo()
    {
        return $this->getDoctrine()->getRepository('App:Report');
    }

    /**
     * @param string $domain
     *
     * @return \App\Entity\Report
     *
     * @throws NonUniqueResultException
     */
    private function getLastReport(Domain $domain)
    {
        if (!$this->lastReport) {
            try {
                $this->lastReport = $this->getReportRepo()->getLastReport($domain->getId());
            } catch (NoResultException $e) {
                throw new NotFoundHttpException('domain is not exists');
            }
        }

        return $this->lastReport;
    }

    /**
     * @param Domain $domain
     *
     * @return int|null
     *
     * @throws NonUniqueResultException
     */
    private function getGlobalRank(Domain $domain)
    {
        $report = $this->getLastReport($domain);

        return $report->getGlobalRank();
    }

    /**
     * @param Domain $domain
     * @param $from
     * @param $till
     *
     * @return array
     */
    private function getGlobalRanks(Domain $domain, $from, $till)
    {
        $result = $this->getReportRepo()->getGlobalRanks($domain->getId(), $from, $till);

        return array_column($result, 'globalRank', 'date');
    }

    /**
     * @param Domain $domain
     *
     * @return int|null
     *
     * @throws NonUniqueResultException
     */
    private function getLocalRank(Domain $domain)
    {
        $report = $this->getLastReport($domain);
        /** @var GeographyRepository $geoRepo */
        $geoRepo = $this->getDoctrine()->getRepository('App:Geography');

        return $geoRepo->getLocalRank($report);
    }

    /**
     * @param Domain $domain
     * @param $from
     * @param $till
     *
     * @return array
     */
    private function getLocalRanks(Domain $domain, $from, $till)
    {
        $result = $this->getReportRepo()->getLocalRanks($domain->getId(), $from, $till);

        return array_column($result, 'rank', 'date');
    }

    /**
     * @param Domain $domain
     * @param $from
     * @param $till
     *
     * @return array
     */
    private function getBounceRates(Domain $domain, $from, $till)
    {
        $result = $this->getReportRepo()->getBounceRates($domain->getId(), $from, $till);

        return array_column($result, 'engageRate', 'date');
    }

    /**
     * @param Domain $domain
     * @param $from
     * @param $till
     *
     * @return array
     */
    private function getPageViews(Domain $domain, $from, $till)
    {
        $result = $this->getReportRepo()->getPageViews($domain->getId(), $from, $till);

        return array_column($result, 'dailyPageView', 'date');
    }

    /**
     * @param Domain $domain
     * @param $from
     * @param $till
     *
     * @return array
     */
    private function getTimeOnSites(Domain $domain, $from, $till)
    {
        $result = $this->getReportRepo()->getTimeOnSites($domain->getId(), $from, $till);

        return array_column($result, 'dailyTimeOnSite', 'date');
    }

    private function getSecondLastReportDate(Domain $domain)
    {
        /** @var Report $report */
        $reports = $this->getReportRepo()->findByDomain($domain, ['date' => 'desc'], 1, 1);

        return $reports ? $reports[0]->getDate() : new \DateTime();
    }
}
