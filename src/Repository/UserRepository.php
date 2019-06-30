<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getAll($limit = null, $offset = 0)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->where($qb->expr()->eq('u.status', User::STATUS_ACTIVE))
            ->setFirstResult($offset);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function loadUserByUsername($username)
    {
        /** @var User $user */
        $user = $this->findOneByUsername($username);

        if (!$user) {
            throw new AuthenticationException('Bad credentials.');
        }

        if (User::STATUS_INACTIVE === $user->getStatus()) {
            throw new AuthenticationException('User is Blocked.');
        }

        return $user;
    }

    public function getCountGroupByProvince()
    {
        $qb = $this->createQueryBuilder('u');

        return $qb->select('count(u) as count, p.name')
            ->innerJoin('u.city', 'c')
            ->innerJoin('c.province', 'p')
            ->groupBy('p.id')
            ->getQuery()
            ->getArrayResult();
    }

    public function getCountGroupByBirthMonth()
    {
        $qb = $this->createQueryBuilder('u');

        return $qb->select('count(u) as count, MONTH(u.birthday) as month')
            ->groupBy('month')
            ->getQuery()
            ->getArrayResult();
    }

//    public function getCountGroupByRegistrationMonth()
//    {
//        $qb = $this->createQueryBuilder('u');
//
//        return $qb->select('count(u) as count, MONTH(u.birthday) as month')
//            ->groupBy('month')
//            ->getQuery()
//            ->getArrayResult();
//    }
}
