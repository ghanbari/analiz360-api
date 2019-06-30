<?php

namespace App\Repository;

use App\Entity\County;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method County|null find($id, $lockMode = null, $lockVersion = null)
 * @method County|null findOneBy(array $criteria, array $orderBy = null)
 * @method County[]    findAll()
 * @method County[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CountyRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, County::class);
    }
}
