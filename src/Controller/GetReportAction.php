<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\DomainFreeWatching;
use App\Entity\Report;
use App\Repository\DomainWatcherRepository;
use App\Repository\OrderRepository;
use App\Repository\ReportRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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

        $history = $this->getAvailableHistory($domain);

        $availableHistory = new \DateTime(sprintf('-%d days', min($history, 30)));
        $availableHistory->setTime(0, 0, 0);
        $date = max($date, $availableHistory);
        $report = $this->getReportRepo()->getFullReport($domain->getId(), $date);

        return $report;
    }

    /**
     * @param Domain $domain
     *
     * @return int|null
     *
     * @throws NonUniqueResultException
     */
    private function getAvailableHistory(Domain $domain)
    {
        $user = $this->getUser();
        /** @var DomainWatcherRepository $domainWatcherRepo */
        $domainWatcherRepo = $this->getDoctrine()->getRepository('App:DomainWatcher');
        $domainWatcher = $domainWatcherRepo->getActivePlan($domain->getId(), $user->getId());
        if ($domainWatcher) {
            $history = $domainWatcher->getHistory();
        } else {
            $history = 3;
            $freeRepo = $this->getDoctrine()->getRepository('App:DomainFreeWatching');
            $freeWatching = $freeRepo->findOneBy(['domain' => $domain->getId(), 'watcher' => $user->getId(), 'createdAt' => new \DateTime()]);
            if (!$freeWatching) {
                $usageCount = $freeRepo->count(['watcher' => $user->getId(), 'createdAt' => new \DateTime()]);
                /** @var OrderRepository $orderRepo */
                $orderRepo = $this->getDoctrine()->getRepository('App:Order');
                $allowedCount = $orderRepo->getDomainFreeWatchingLimitation($user->getId());

                if ($usageCount < $allowedCount) {
                    $freeWatching = new DomainFreeWatching($domain, $user);
                    $this->getDoctrine()->getManager()->persist($freeWatching);
                    $this->getDoctrine()->getManager()->flush($freeWatching);
                } else {
                    throw new AccessDeniedHttpException('You must buy a plan');
                }
            }
        }

        return $history;
    }

    /**
     * @return ReportRepository
     */
    private function getReportRepo()
    {
        return $this->getDoctrine()->getRepository('App:Report');
    }
}
