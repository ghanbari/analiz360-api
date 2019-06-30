<?php

namespace App\Repository;

use App\Entity\RegisterRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method RegisterRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method RegisterRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method RegisterRequest[]    findAll()
 * @method RegisterRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegisterRequestRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RegisterRequest::class);
    }

    public function getCountOfRequest(\DateTime $fromTime = null, string $phone = null, string $ip = null)
    {
        $qb = $this->createQueryBuilder('rr');

        $qb->select('count(rr.id)');

        if (!is_null($phone)) {
            $qb->andWhere($qb->expr()->eq('rr.phone', ':phone'))
                ->setParameter('phone', $phone);
        }

        if (!is_null($ip)) {
            $qb->andWhere($qb->expr()->eq('rr.ip', ':ip'))
                ->setParameter('ip', $ip);
        }

        if (!is_null($fromTime)) {
            $qb->andWhere($qb->expr()->gte('rr.requestedAt', ':fromTime'))
                ->setParameter('fromTime', $fromTime);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string $phone
     *
     * @return RegisterRequest
     */
    public function getLast(string $phone)
    {
        $qb = $this->createQueryBuilder('rr');

        $result = $qb->select('rr')
            ->where($qb->expr()->eq('rr.phone', $phone))
            ->andWhere($qb->expr()->eq('rr.isValid', ':valid'))
            ->setParameter('valid', true)
            ->orderBy('rr.requestedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return !(empty($result)) ? $result[0] : null;
    }

    public function invalidatePreRequests(RegisterRequest $registerRequest)
    {
        $qb = $this->createQueryBuilder('rr');

        $qb->update()
            ->set('rr.isValid', 0)
            ->where($qb->expr()->eq('rr.phone', ':phone'))
            ->setParameter('phone', $registerRequest->getPhone())
            ->getQuery()
            ->execute();
    }
}
