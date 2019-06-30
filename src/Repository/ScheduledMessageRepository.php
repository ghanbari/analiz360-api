<?php

namespace App\Repository;

use App\Entity\ScheduledMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ScheduledMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScheduledMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScheduledMessage[]    findAll()
 * @method ScheduledMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScheduledMessageRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ScheduledMessage::class);
    }

    /**
     * @return ScheduledMessage[]
     *
     * @throws \Exception
     */
    public function getAll()
    {
        return $this->getAllQueryBuilder()
            ->getQuery()
            ->getResult();
    }

    public function getAllQueryBuilder()
    {
        $qb = $this->createQueryBuilder('ss');

        $qb
            ->where($qb->expr()->eq('ss.expired', ':notExpired'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('ss.expireAt'),
                $qb->expr()->gte('ss.expireAt', ':now')
            ))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('ss.startAt'),
                $qb->expr()->lte('ss.startAt', ':now')
            ))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('ss.maxUsageCount'),
                    $qb->expr()->lt('ss.usageCount', 'ss.maxUsageCount')
                )
            )
            ->setParameter('notExpired', false)
            ->setParameter('now', new \DateTime());

        return $qb;
    }

    public function getAllFilterByTypes(array $types)
    {
        $qb = $this->getAllQueryBuilder();

        return $qb
            ->andWhere($qb->expr()->in('ss.dateType', ':types'))
            ->setParameter('types', $types)
            ->getQuery()
            ->getResult();
    }
}
