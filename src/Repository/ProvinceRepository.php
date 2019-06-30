<?php

namespace App\Repository;

use App\Entity\Province;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Province|null find($id, $lockMode = null, $lockVersion = null)
 * @method Province|null findOneBy(array $criteria, array $orderBy = null)
 * @method Province[]    findAll()
 * @method Province[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProvinceRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Province::class);
    }
}
