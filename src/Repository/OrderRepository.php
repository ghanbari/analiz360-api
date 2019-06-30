<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function getDomainFreeWatchingLimitation($userId, $default = 3)
    {
        $qb = $this->createQueryBuilder('o');

        $result = $qb->select('count(o) as count')
            ->innerJoin('o.product', 'p')
            ->innerJoin('o.user', 'u')
            ->where($qb->expr()->eq('u.id', ':userId'))
            ->andWhere($qb->expr()->eq('p.type', Product::TYPE_ALEXA_INCREASE_FREE_WATCH_SIZE))
            ->andWhere($qb->expr()->gte("date_add(o.createdAt, JSON_EXTRACT(p.service , '$.duration'), 'DAY')", 'now()'))
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $default + $result;
    }

    // /**
    //  * @return Order[] Returns an array of Order objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
