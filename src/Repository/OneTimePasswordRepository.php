<?php

namespace App\Repository;

use App\Entity\OneTimePassword;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method OneTimePassword|null find($id, $lockMode = null, $lockVersion = null)
 * @method OneTimePassword|null findOneBy(array $criteria, array $orderBy = null)
 * @method OneTimePassword[]    findAll()
 * @method OneTimePassword[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OneTimePasswordRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, OneTimePassword::class);
    }

    public function getCountOfRequest(\DateTime $fromTime = null, string $receptor = null, string $ip = null)
    {
        $qb = $this->createQueryBuilder('otp');

        $qb->select('count(otp.id)');

        if (!is_null($receptor)) {
            $qb->andWhere($qb->expr()->eq('otp.receptor', ':receptor'))
                ->setParameter('receptor', $receptor);
        }

        if (!is_null($ip)) {
            $qb->andWhere($qb->expr()->eq('otp.ip', ':ip'))
                ->setParameter('ip', $ip);
        }

        if (!is_null($fromTime)) {
            $qb->andWhere($qb->expr()->gte('otp.requestedAt', ':fromTime'))
                ->setParameter('fromTime', $fromTime);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string $receptor
     *
     * @return OneTimePassword
     */
    public function getLast(string $receptor)
    {
        $qb = $this->createQueryBuilder('otp');

        $result = $qb->select('otp')
            ->where($qb->expr()->eq('otp.receptor', ':receptor'))
            ->andWhere($qb->expr()->eq('otp.isValid', ':valid'))
            ->setParameter('valid', true)
            ->setParameter('receptor', $receptor)
            ->orderBy('otp.requestedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return !(empty($result)) ? $result[0] : null;
    }

    public function invalidatePrePasswords(OneTimePassword $oneTimePassword)
    {
        $qb = $this->createQueryBuilder('otp');

        $qb->update()
            ->set('otp.isValid', 0)
            ->where($qb->expr()->eq('otp.receptor', ':receptor'))
            ->setParameter('receptor', $oneTimePassword->getReceptor())
            ->getQuery()
            ->execute();
    }
}
