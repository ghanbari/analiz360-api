<?php

namespace App\Repository;

use App\Entity\DomainWatcher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DomainWatcher|null find($id, $lockMode = null, $lockVersion = null)
 * @method DomainWatcher|null findOneBy(array $criteria, array $orderBy = null)
 * @method DomainWatcher[]    findAll()
 * @method DomainWatcher[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomainWatcherRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DomainWatcher::class);
    }

    public function getActivePlanQueryBuilder(int $domainId, int $userId)
    {
        $qb = $this->createQueryBuilder('dw');

        return $qb->select(['dw', 'd', 'w'])
            ->innerJoin('dw.domain', 'd')
            ->innerJoin('dw.watcher', 'w')
            ->where($qb->expr()->eq('d.id', ':domainId'))
            ->andWhere($qb->expr()->eq('w.id', ':userId'))
            ->andWhere($qb->expr()->gte('dw.expireAt', 'now()'))
            ->setParameter('domainId', $domainId)
            ->setParameter('userId', $userId);
    }

    /**
     * @param int $domainId
     * @param int $userId
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return DomainWatcher
     */
    public function getActivePlan(int $domainId, int $userId)
    {
        return $this->getActivePlanQueryBuilder($domainId, $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $domainId
     * @param int $userId
     *
     * @return DomainWatcher[]
     */
    public function getActivePlans(int $domainId, int $userId)
    {
        return $this->getActivePlanQueryBuilder($domainId, $userId)
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return DomainWatcher[] Returns an array of DomainWatcher objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DomainWatcher
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
