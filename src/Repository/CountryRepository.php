<?php

namespace App\Repository;

use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Country|null find($id, $lockMode = null, $lockVersion = null)
 * @method Country|null findOneBy(array $criteria, array $orderBy = null)
 * @method Country[]    findAll()
 * @method Country[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CountryRepository extends ServiceEntityRepository
{
    private static $countries;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Country::class);
    }

    public function findCountryId($alpha2): ?Country
    {
        if (is_null(self::$countries) || (array_key_exists($alpha2, self::$countries) && !$this->getEntityManager()->contains(self::$countries[$alpha2]))) {
            $qb = $this->createQueryBuilder('c');

            $countries = $qb->select('c')
                ->getQuery()
                ->getResult();

            $result = [];
            foreach ($countries as $country) {
                $result[strtoupper($country->getAlpha2())] = $country;
            }

            self::$countries = $result;
        }

        return isset(self::$countries[$alpha2]) ? self::$countries[$alpha2] : null;
    }
}
