<?php

namespace App\Repository;

use App\Entity\Domain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Domain|null find($id, $lockMode = null, $lockVersion = null)
 * @method Domain|null findOneBy(array $criteria, array $orderBy = null)
 * @method Domain[]    findAll()
 * @method Domain[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomainRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Domain::class);
    }

    public function getQueue($count, $tryAfter): array
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('d')
            ->where($qb->expr()->eq('d.status', Domain::STATUS_ACTIVE))
            ->andWhere($qb->expr()->not($qb->expr()->exists('select 1 from App\Entity\Report r where r.date = CURRENT_DATE() and r.domain = d.id')))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('d.lastReportStatus'),
                $qb->expr()->andX(
                    $qb->expr()->eq('d.lastReportStatus', ':successfulStatus'),
                    $qb->expr()->lte("
                    CASE
                        WHEN d.owner IS NOT NULL THEN DATEADD(DATE(d.lastReportAt), 1, 'DAY')
                        WHEN d.score IS NULL THEN DATEADD(DATE(d.lastReportAt), 1, 'DAY')
                        WHEN d.score > 15 THEN DATEADD(DATE(d.lastReportAt), 1, 'DAY')
                        WHEN d.score > 9 THEN DATEADD(DATE(d.lastReportAt), 3, 'DAY')
                        WHEN d.score > 5 THEN DATEADD(DATE(d.lastReportAt), 4, 'DAY')
                        WHEN d.score > 2 THEN DATEADD(DATE(d.lastReportAt), 5, 'DAY')
                        WHEN d.score > 1 THEN DATEADD(DATE(d.lastReportAt), 6, 'DAY')
                        WHEN d.score > 0 THEN DATEADD(DATE(d.lastReportAt), 7, 'DAY')
                        ELSE DATEADD(DATE(d.lastReportAt), 10, 'DAY')
                    END
                    ", 'CURRENT_DATE()')
                ),
                $qb->expr()->andX(
                    $qb->expr()->eq('d.lastReportStatus', ':notFoundStatus'),
                    $qb->expr()->lte("
                    CASE
                        WHEN d.score > 15 THEN TIMESTAMPADD(HOUR, 3, d.lastReportAt)
                        WHEN (d.score > 9 OR d.lastReportQuality > 70) THEN TIMESTAMPADD(HOUR, 5, d.lastReportAt)
                        WHEN (d.score > 5 OR d.lastReportQuality > 50) THEN TIMESTAMPADD(HOUR, 8, d.lastReportAt)
                        WHEN d.score > 2 THEN TIMESTAMPADD(HOUR, 16, d.lastReportAt)
                        WHEN d.score > 1 THEN TIMESTAMPADD(HOUR, 24, d.lastReportAt)
                        WHEN d.score > 0 THEN TIMESTAMPADD(HOUR, 32, d.lastReportAt)
                        ELSE DATEADD(DATE(d.lastReportAt), 10, 'DAY')
                    END
                    ", 'CURRENT_DATE()')
                ),
                $qb->expr()->andX(
                    $qb->expr()->in('d.lastReportStatus', ':failedStatus'),
                    '(UNIX_TIMESTAMP() - UNIX_TIMESTAMP(d.lastReportAt) > :tryAfter)'
                )
            ))
            ->orderBy('d.lastReportStatus', 'DESC')
            ->addOrderBy('d.lastReportQuality', 'DESC')
            ->addOrderBy('d.score', 'DESC')
            ->setParameter('failedStatus', [Domain::REPORT_FAILED, Domain::REPORT_IN_PROGRESS])
            ->setParameter('successfulStatus', Domain::REPORT_FINISHED)
            ->setParameter('notFoundStatus', Domain::REPORT_NOT_FOUND)
            ->setParameter('tryAfter', $tryAfter);

        $domains = $qb->getQuery()
            ->setMaxResults($count)
            ->getResult();

        $domainNames = array_map(function (Domain $domain) {
            return $domain->getDomain();
        }, $domains);

        $qb = $this->createQueryBuilder('d');
        $qb->update()
            ->set('d.lastReportAt', ':now')
            ->set('d.lastReportStatus', Domain::REPORT_IN_PROGRESS)
            ->where($qb->expr()->in('d.domain', ':domainNames'))
            ->setParameter('now', new \DateTime())
            ->setParameter('domainNames', $domainNames)
            ->getQuery()
            ->execute();

        foreach ($domains as $domain) {
            $this->getEntityManager()->getUnitOfWork()->setOriginalEntityProperty(
                spl_object_hash($domain),
                'lastReportStatus',
                Domain::REPORT_IN_PROGRESS
            );
        }

        return $domains;
    }

    public function getAuditQueue($count): array
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('d')
            ->where($qb->expr()->eq('d.status', Domain::STATUS_ACTIVE))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('d.lastAuditStatus'),
                $qb->expr()->andX(
                    $qb->expr()->eq('d.lastAuditStatus', Domain::REPORT_NOT_FOUND),
                    '(UNIX_TIMESTAMP() - UNIX_TIMESTAMP(d.lastReportAt) > 259200)' // after 3 day
                ),
                $qb->expr()->andX(
                    $qb->expr()->eq('d.lastAuditStatus', Domain::REPORT_FINISHED),
                    '(UNIX_TIMESTAMP() - UNIX_TIMESTAMP(d.lastAuditAt) > 604800)' // after 7 day
                ),
                $qb->expr()->andX(
                    $qb->expr()->in('d.lastAuditStatus', ':status'),
                    '(UNIX_TIMESTAMP() - UNIX_TIMESTAMP(d.lastAuditAt) > 86400)' // after 1 day
                )
            ))
            ->setParameter('status', [Domain::REPORT_FAILED, Domain::REPORT_IN_PROGRESS]);

        $domains = $qb->getQuery()
            ->setMaxResults($count)
            ->getResult();

        $ids = array_map(function (Domain $domain) {
            return $domain->getId();
        }, $domains);

        $qb = $this->createQueryBuilder('d');
        $qb->update()
            ->set('d.lastAuditAt', ':now')
            ->set('d.lastAuditStatus', Domain::REPORT_IN_PROGRESS)
            ->where($qb->expr()->in('d.id', ':ids'))
            ->setParameter('now', new \DateTime())
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();

        foreach ($domains as $domain) {
            $this->getEntityManager()->getUnitOfWork()->setOriginalEntityProperty(
                spl_object_hash($domain),
                'lastAuditStatus',
                Domain::REPORT_IN_PROGRESS
            );
        }

        return $domains;
    }

    public function getTopAscent($limit = 5, $minRank = 0, $maxRank = 5000)
    {
        $qb = $this->createQueryBuilder('d');

        return $qb->select(['d.id', 'd.domain', 'lr.globalRank', '(fr.globalRank - lr.globalRank) as change'])
            ->innerJoin('App:Report', 'fr', \Doctrine\ORM\Query\Expr\Join::WITH, "d.id = fr.domain and fr.date = DATE_SUB(CURRENT_DATE(), 7, 'DAY')")
            ->innerJoin('App:Report', 'lr', \Doctrine\ORM\Query\Expr\Join::WITH, 'd.id = lr.domain and lr.date = CURRENT_DATE()')
            ->where($qb->expr()->gt('lr.globalRank', $minRank))
            ->where($qb->expr()->lt('lr.globalRank', $maxRank))
            ->orderBy('change', 'desc')
            ->setFirstResult(0)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

//    public function getTopAscentLocal($localAlpha2Code, $limit = 5, $minRank = 0, $maxRank = 5000)
//    {
//        $qb = $this->createQueryBuilder('d');
//
//        return $qb->select(['d.id', 'd.domain', 'lr.globalRank', '(fr.globalRank - lr.globalRank) as change'])
//            ->innerJoin('App:Report', 'fr', \Doctrine\ORM\Query\Expr\Join::WITH, "d.id = fr.domain and fr.date = DATE_SUB(CURRENT_DATE(), 7, 'DAY')")
//            ->innerJoin('App:Report', 'lr', \Doctrine\ORM\Query\Expr\Join::WITH, 'd.id = lr.domain and lr.date = CURRENT_DATE()')
//            ->where($qb->expr()->gt('lr.globalRank', $minRank))
//            ->where($qb->expr()->lt('lr.globalRank', $maxRank))
//            ->orderBy('change', 'desc')
//            ->setFirstResult(0)
//            ->setMaxResults($limit)
//            ->getQuery()
//            ->getResult();
//    }

    public function getTopDescent($limit = 5, $minRank = 0, $maxRank = 5000)
    {
        $qb = $this->createQueryBuilder('d');

        return $qb->select(['d.id', 'd.domain', 'lr.globalRank', '(fr.globalRank - lr.globalRank) as change'])
            ->innerJoin('App:Report', 'fr', \Doctrine\ORM\Query\Expr\Join::WITH, "d.id = fr.domain and fr.date = DATE_SUB(CURRENT_DATE(), 7, 'DAY')")
            ->innerJoin('App:Report', 'lr', \Doctrine\ORM\Query\Expr\Join::WITH, 'd.id = lr.domain and lr.date = CURRENT_DATE()')
            ->where($qb->expr()->gt('lr.globalRank', $minRank))
            ->where($qb->expr()->lt('lr.globalRank', $maxRank))
            ->orderBy('change', 'asc')
            ->setFirstResult(0)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTopStable($limit = 5, $minRank = 0, $maxRank = 5000)
    {
        $qb = $this->createQueryBuilder('d');

        return $qb->select(['d.id', 'd.domain', 'lr.globalRank', '(abs(fr.globalRank - lr.globalRank)) as change'])
            ->innerJoin('App:Report', 'fr', \Doctrine\ORM\Query\Expr\Join::WITH, "d.id = fr.domain and fr.date = DATE_SUB(CURRENT_DATE(), 7, 'DAY')")
            ->innerJoin('App:Report', 'lr', \Doctrine\ORM\Query\Expr\Join::WITH, 'd.id = lr.domain and lr.date = CURRENT_DATE()')
            ->where($qb->expr()->gt('lr.globalRank', $minRank))
            ->where($qb->expr()->lt('lr.globalRank', $maxRank))
            ->orderBy('change', 'asc')
            ->addOrderBy('fr.globalRank', 'asc')
            ->setFirstResult(0)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
