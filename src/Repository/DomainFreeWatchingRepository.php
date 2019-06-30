<?php

namespace App\Repository;

use App\Entity\DomainFreeWatching;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DomainFreeWatching|null find($id, $lockMode = null, $lockVersion = null)
 * @method DomainFreeWatching|null findOneBy(array $criteria, array $orderBy = null)
 * @method DomainFreeWatching[]    findAll()
 * @method DomainFreeWatching[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomainFreeWatchingRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DomainFreeWatching::class);
    }

    // /**
    //  * @return DomainFreeWatching[] Returns an array of DomainFreeWatching objects
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
    public function findOneBySomeField($value): ?DomainFreeWatching
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
