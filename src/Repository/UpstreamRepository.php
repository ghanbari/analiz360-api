<?php

namespace App\Repository;

use App\Entity\Upstream;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Upstream|null find($id, $lockMode = null, $lockVersion = null)
 * @method Upstream|null findOneBy(array $criteria, array $orderBy = null)
 * @method Upstream[]    findAll()
 * @method Upstream[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UpstreamRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Upstream::class);
    }

    // /**
    //  * @return Upstream[] Returns an array of Upstream objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Upstream
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
