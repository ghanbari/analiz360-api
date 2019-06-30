<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Report|null find($id, $lockMode = null, $lockVersion = null)
 * @method Report|null findOneBy(array $criteria, array $orderBy = null)
 * @method Report[]    findAll()
 * @method Report[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Report::class);
    }

    public function getAllQueryBuilder()
    {
        return $this->createQueryBuilder('r');
    }

    /**
     * @param int $domain
     *
     * @return Report
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLastReport(int $domain)
    {
        $qb = $this->createQueryBuilder('r');

        return $qb->select('r')
            ->where($qb->expr()->eq('r.domain', ':domain'))
            ->setParameter('domain', $domain)
            ->orderBy('r.date', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @param int $domain
     * @param $from
     * @param $till
     *
     * @return array
     */
    public function getGlobalRanks(int $domain, $from, $till)
    {
        $qb = $this->createQueryBuilder('r');

        return $qb->select(['r.globalRank', 'r.date'])
            ->where($qb->expr()->eq('r.domain', ':domain'))
            ->andWhere($qb->expr()->gte('r.date', ':from'))
            ->andWhere($qb->expr()->lte('r.date', ':till'))
            ->setParameter('domain', $domain)
            ->setParameter('from', $from)
            ->setParameter('till', $till)
            ->orderBy('r.date', 'asc')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @param int $domain
     * @param $from
     * @param $till
     *
     * @return array
     */
    public function getLocalRanks(int $domain, $from, $till)
    {
        $qb = $this->createQueryBuilder('r');

        return $qb->select(['g.rank', 'r.date'])
            ->innerJoin('r.geographies', 'g')
            ->innerJoin('g.country', 'c')
            ->where($qb->expr()->eq('r.domain', ':domain'))
            ->andWhere($qb->expr()->gte('r.date', ':from'))
            ->andWhere($qb->expr()->lte('r.date', ':till'))
            ->andWhere($qb->expr()->eq('c.alpha2', ':country'))
            ->setParameter('domain', $domain)
            ->setParameter('from', $from)
            ->setParameter('till', $till)
            ->setParameter('country', 'ir')
            ->orderBy('r.date', 'asc')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @param int $domain
     * @param $from
     * @param $till
     *
     * @return array
     */
    public function getBounceRates(int $domain, $from, $till)
    {
        $qb = $this->createQueryBuilder('r');

        return $qb->select(['r.engageRate', 'r.date'])
            ->where($qb->expr()->eq('r.domain', ':domain'))
            ->andWhere($qb->expr()->gte('r.date', ':from'))
            ->andWhere($qb->expr()->lte('r.date', ':till'))
            ->setParameter('domain', $domain)
            ->setParameter('from', $from)
            ->setParameter('till', $till)
            ->orderBy('r.date', 'asc')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @param int $domain
     * @param $from
     * @param $till
     *
     * @return array
     */
    public function getPageViews(int $domain, $from, $till)
    {
        $qb = $this->createQueryBuilder('r');

        return $qb->select(['r.dailyPageView', 'r.date'])
            ->where($qb->expr()->eq('r.domain', ':domain'))
            ->andWhere($qb->expr()->gte('r.date', ':from'))
            ->andWhere($qb->expr()->lte('r.date', ':till'))
            ->setParameter('domain', $domain)
            ->setParameter('from', $from)
            ->setParameter('till', $till)
            ->orderBy('r.date', 'asc')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @param int $domain
     * @param $from
     * @param $till
     *
     * @return array
     */
    public function getTimeOnSites(int $domain, $from, $till)
    {
        $qb = $this->createQueryBuilder('r');

        return $qb->select(['r.dailyTimeOnSite', 'r.date'])
            ->where($qb->expr()->eq('r.domain', ':domain'))
            ->andWhere($qb->expr()->gte('r.date', ':from'))
            ->andWhere($qb->expr()->lte('r.date', ':till'))
            ->setParameter('domain', $domain)
            ->setParameter('from', $from)
            ->setParameter('till', $till)
            ->orderBy('r.date', 'asc')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @param $count
     *
     * @return array
     */
    public function getScoreQueue($count): array
    {
        $qb = $this->createQueryBuilder('r');
        $reportIds = $qb->select('r.id')
            ->innerJoin('r.domain', 'd')
            ->where($qb->expr()->eq('d.status', Domain::STATUS_ACTIVE))
            ->andWhere($qb->expr()->eq('r.date', 'CURRENT_DATE()'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('d.score'),
                $qb->expr()->isNull('d.scoreUpdatedAt'),
                "DATEADD(d.scoreUpdatedAt, 2, 'DAY') < CURRENT_DATE()"
            ))
            ->setMaxResults($count)
            ->getQuery()
            ->getScalarResult();

        $qb2 = $this->createQueryBuilder('r');

        return $qb2->select(['r', 'd', 'g', 'c'])
            ->innerJoin('r.domain', 'd')
            ->leftJoin('r.geographies', 'g')
            ->leftJoin('g.country', 'c')
            ->where($qb2->expr()->in('r.id', ':reportIds'))
            ->setParameter('reportIds', $reportIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int       $domainId
     * @param \DateTime $date
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getFullReport(int $domainId, \DateTime $date)
    {
        $qb = $this->createQueryBuilder('r');

        return $qb->select(['r', 'g', 'b', 'k', 't', 'u'])
            ->innerJoin('r.geographies', 'g')
            ->innerJoin('r.backlinks', 'b')
            ->innerJoin('r.keywords', 'k')
            ->innerJoin('r.toppages', 't')
            ->innerJoin('r.upstreams', 'u')
            ->where($qb->expr()->eq('r.domain', ':domain'))
            ->andWhere($qb->expr()->eq('r.date', ':date'))
            ->setParameter('domain', $domainId)
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleResult();
    }
}
