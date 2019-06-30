<?php

namespace App\Repository;

use App\Entity\Toppage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Toppage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Toppage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Toppage[]    findAll()
 * @method Toppage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ToppageRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Toppage::class);
    }

    // /**
    //  * @return Toppage[] Returns an array of Toppage objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Toppage
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
