<?php

namespace App\Repository;

use App\Entity\DomainAudit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DomainAudit|null find($id, $lockMode = null, $lockVersion = null)
 * @method DomainAudit|null findOneBy(array $criteria, array $orderBy = null)
 * @method DomainAudit[]    findAll()
 * @method DomainAudit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomainAuditRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DomainAudit::class);
    }

    // /**
    //  * @return DomainAudit[] Returns an array of DomainAudit objects
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
    public function findOneBySomeField($value): ?DomainAudit
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
