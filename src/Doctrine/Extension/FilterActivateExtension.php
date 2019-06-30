<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

class FilterActivateExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security, Reader $reader, EntityManagerInterface $entityManager)
    {
        $this->reader = $reader;
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = [])
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $user = $this->security->getUser();
        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            return;
        }

        $metadata = $this->entityManager->getClassMetadata($resourceClass);

        if (property_exists($resourceClass, 'active')) {
            /** @var Column $active */
            $active = $this->reader->getPropertyAnnotation($metadata->getReflectionProperty('active'), Column::class);
            if ('bool' === $active->type) {
                $rootAlias = $queryBuilder->getRootAliases()[0];
                $queryBuilder->andWhere(sprintf('%s.active = :active', $rootAlias));
                $queryBuilder->setParameter('active', true);
            }
        }
    }
}
