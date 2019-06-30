<?php

namespace App\Repository;

use App\Entity\RelatedDomain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method RelatedDomain|null find($id, $lockMode = null, $lockVersion = null)
 * @method RelatedDomain|null findOneBy(array $criteria, array $orderBy = null)
 * @method RelatedDomain[]    findAll()
 * @method RelatedDomain[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RelatedDomainRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RelatedDomain::class);
    }

    // /**
    //  * @return RelatedDomain[] Returns an array of RelatedDomain objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RelatedDomain
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
