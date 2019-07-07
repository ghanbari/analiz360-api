<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\DomainFreeWatching;
use App\Entity\DomainWatcher;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @method DomainWatcher|null find($id, $lockMode = null, $lockVersion = null)
 * @method DomainWatcher|null findOneBy(array $criteria, array $orderBy = null)
 * @method DomainWatcher[]    findAll()
 * @method DomainWatcher[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomainWatcherRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DomainWatcher::class);
    }

    public function getActivePlanQueryBuilder(int $domainId, int $userId)
    {
        $qb = $this->createQueryBuilder('dw');

        return $qb->select(['dw', 'd', 'w'])
            ->innerJoin('dw.domain', 'd')
            ->innerJoin('dw.watcher', 'w')
            ->where($qb->expr()->eq('d.id', ':domainId'))
            ->andWhere($qb->expr()->eq('w.id', ':userId'))
            ->andWhere($qb->expr()->gte('dw.expireAt', 'now()'))
            ->setParameter('domainId', $domainId)
            ->setParameter('userId', $userId);
    }

    /**
     * @param int $domainId
     * @param int $userId
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return DomainWatcher
     */
    public function getActivePlan(int $domainId, int $userId)
    {
        return $this->getActivePlanQueryBuilder($domainId, $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $domainId
     * @param int $userId
     *
     * @return DomainWatcher[]
     */
    public function getActivePlans(int $domainId, int $userId)
    {
        return $this->getActivePlanQueryBuilder($domainId, $userId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Domain $domain
     * @param User   $user
     *
     * @return int|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getAvailableHistory(Domain $domain, User $user)
    {
        $domainWatcher = $this->getActivePlan($domain->getId(), $user->getId());
        if ($domainWatcher) {
            $history = $domainWatcher->getHistory();
        } else {
            $history = 3;
            $freeRepo = $this->getEntityManager()->getRepository('App:DomainFreeWatching');
            //TODO: createdAt is DateTime and not date
            $freeWatching = $freeRepo->findOneBy(['domain' => $domain->getId(), 'watcher' => $user->getId(), 'createdAt' => new \DateTime()]);
            if (!$freeWatching) {
                $usageCount = $freeRepo->count(['watcher' => $user->getId(), 'createdAt' => new \DateTime()]);
                /** @var OrderRepository $orderRepo */
                $orderRepo = $this->getEntityManager()->getRepository('App:Order');
                $allowedCount = $orderRepo->getDomainFreeWatchingLimitation($user->getId());

                if ($usageCount < $allowedCount) {
                    $freeWatching = new DomainFreeWatching($domain, $user);
                    $this->getEntityManager()->persist($freeWatching);
                    $this->getEntityManager()->flush($freeWatching);
                } else {
                    throw new AccessDeniedHttpException('You must buy a plan');
                }
            }
        }

        return $history;
    }
}
