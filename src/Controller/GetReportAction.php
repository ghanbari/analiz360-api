<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\Report;
use App\Repository\DomainWatcherRepository;
use App\Repository\ReportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetReportAction extends AbstractController
{
    public function __invoke(Request $request): Report
    {
        $date = $request->attributes->get('date', date('Y-m-d'));
        $date = new \DateTime($date);
        $domainId = $request->attributes->get('id');
        /** @var Domain $domain */
        $domain = $this->getDoctrine()->getRepository('App:Domain')->find($domainId);

        if (!$domain) {
            throw new NotFoundHttpException('Domain is not exists.');
        }

        /** @var DomainWatcherRepository $domainWatcherRepo */
        $domainWatcherRepo = $this->getDoctrine()->getRepository('App:DomainWatcher');
        $history = $domainWatcherRepo->getAvailableHistory($domain, $this->getUser());

        $availableHistory = new \DateTime(sprintf('-%d days', min($history, 30)));
        $availableHistory->setTime(0, 0, 0);
        $date = max($date, $availableHistory);
        $report = $this->getReportRepo()->getFullReport($domain->getId(), $date);

        return $report;
    }

    /**
     * @return ReportRepository
     */
    private function getReportRepo()
    {
        return $this->getDoctrine()->getRepository('App:Report');
    }
}
