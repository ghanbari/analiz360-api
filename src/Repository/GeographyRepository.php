<?php

namespace App\Repository;

use App\Entity\Geography;
use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Geography|null find($id, $lockMode = null, $lockVersion = null)
 * @method Geography|null findOneBy(array $criteria, array $orderBy = null)
 * @method Geography[]    findAll()
 * @method Geography[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeographyRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Geography::class);
    }

    /**
     * @param Report $report
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLocalRank(Report $report)
    {
        $qb = $this->createQueryBuilder('g');

        return $qb->select('g.rank')
            ->innerJoin('g.country', 'c')
            ->where($qb->expr()->eq('g.report', ':report'))
            ->andWhere($qb->expr()->eq('c.alpha2', ':country'))
            ->setParameter('report', $report)
            ->setParameter('country', 'ir')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
