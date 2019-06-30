<?php

namespace App\Repository;

use App\Entity\Backlink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Backlink|null find($id, $lockMode = null, $lockVersion = null)
 * @method Backlink|null findOneBy(array $criteria, array $orderBy = null)
 * @method Backlink[]    findAll()
 * @method Backlink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BacklinkRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Backlink::class);
    }

    // /**
    //  * @return Backlink[] Returns an array of Backlink objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Backlink
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
