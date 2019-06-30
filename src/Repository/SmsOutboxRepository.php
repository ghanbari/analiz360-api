<?php

namespace App\Repository;

use App\Entity\SmsOutbox;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method SmsOutbox|null find($id, $lockMode = null, $lockVersion = null)
 * @method SmsOutbox|null findOneBy(array $criteria, array $orderBy = null)
 * @method SmsOutbox[]    findAll()
 * @method SmsOutbox[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SmsOutboxRepository extends ServiceEntityRepository
{
    /**
     * SmsOutboxRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SmsOutbox::class);
    }

    /**
     * @param int $count
     * @param $maxCheckCount
     *
     * @return SmsOutbox[]
     */
    public function getUndefinedStatus(int $count, $maxCheckCount)
    {
        $qb = $this->createQueryBuilder('so');

        return $qb
            ->select('so', 'message')
            ->innerJoin('so.message', 'message')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in('so.status', ':queueStatus'),
                    $qb->expr()->notIn('message.status', ':finishedStatus')
                )
            )->andWhere($qb->expr()->isNotNull('so.trackingCode'))
            ->andWhere($qb->expr()->lt('so.statusCheckCount', $maxCheckCount))
            ->setParameter('queueStatus', [SmsOutbox::STATUS_IN_QUEUE, SmsOutbox::STATUS_SCHEDULED, SmsOutbox::STATUS_SEND_TO_TELECOMS])
            ->setParameter('finishedStatus', [SmsOutbox::STATUS_DELIVERED, SmsOutbox::STATUS_BLOCKED])
            ->orderBy('message.priority', 'DESC')
            ->addOrderBy(
                'ifelse(
                                    (unix_timestamp(so.sendTime) + message.timeout) - unix_timestamp() > 0 ,
                                    (unix_timestamp(so.sendTime) + message.timeout) - unix_timestamp(),
                                    9999999
                                    )',
                'ASC'
            )
            ->addOrderBy('so.sendTime', 'DESC')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult();
    }
}
