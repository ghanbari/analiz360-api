<?php

namespace App\Repository;

use App\Entity\DomainVerify;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DomainVerify|null find($id, $lockMode = null, $lockVersion = null)
 * @method DomainVerify|null findOneBy(array $criteria, array $orderBy = null)
 * @method DomainVerify[]    findAll()
 * @method DomainVerify[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomainVerifyRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DomainVerify::class);
    }

    // /**
    //  * @return DomainVerify[] Returns an array of DomainVerify objects
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
    public function findOneBySomeField($value): ?DomainVerify
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
