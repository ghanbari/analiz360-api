<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\DomainFreeWatching;
use App\Entity\Report;
use App\Repository\DomainWatcherRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;

class ReportAccessCheckExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private $security;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * ReportAccessCheckExtension constructor.
     *
     * @param Security               $security
     * @param EntityManagerInterface $entityManager
     * @param RequestStack           $requestStack
     */
    public function __construct(Security $security, EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
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
        if (Report::class !== $resourceClass || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $this->removeDateWhere($queryBuilder, $resourceClass);

        $alias = $queryBuilder->getRootAliases()[0];
        $request = $this->requestStack->getMasterRequest();
        $dateQuery = $request->query->get('date', []);
        $domainId = $request->attributes->get('id');
        $user = $this->security->getUser();

        $domain = $this->entityManager->getRepository('App:Domain')->find($domainId);
        if (!$domain) {
            throw new NotFoundHttpException('Domain is not exists.');
        }

        /** @var DomainWatcherRepository $domainWatcherRepo */
        $domainWatcherRepo = $this->entityManager->getRepository('App:DomainWatcher');
        $domainWatcher = $domainWatcherRepo->getActivePlan($domainId, $this->security->getUser()->getId());
        if ($domainWatcher) {
            $history = $domainWatcher->getHistory();
        } else {
            $history = 3;
            $freeRepo = $this->entityManager->getRepository('App:DomainFreeWatching');
            $freeWatching = $freeRepo->findOneBy(['domain' => $domainId, 'watcher' => $user->getId(), 'createdAt' => new \DateTime()]);
            if (!$freeWatching) {
                $usageCount = $freeRepo->count(['watcher' => $user->getId(), 'createdAt' => new \DateTime()]);
                $allowedCount = $this->entityManager->getRepository('App:Order')->getDomainFreeWatchingLimitation($user->getId());

                if ($usageCount < $allowedCount) {
                    $freeWatching = new DomainFreeWatching($domain, $user);
                    $this->entityManager->persist($freeWatching);
                    $this->entityManager->flush($freeWatching);
                } else {
                    throw new AccessDeniedHttpException('You must buy a plan');
                }
            }
        }

        $from = new \DateTime(sprintf('-%d days', min($history, 30)));
        if (isset($dateQuery['after'])) {
            try {
                $after = new \DateTime($dateQuery['after']);
                $from = max($after, $from);
            } catch (\Exception $e) {
                //TODO: log warning
            }
        }

        $till = new \DateTime();
        if (isset($dateQuery['before'])) {
            try {
                $before = new \DateTime($dateQuery['before']);
                $till = min($before, $till);
            } catch (\Exception $e) {
                //TODO: log warning
            }
        }

        $queryBuilder->andWhere($queryBuilder->expr()->gte(sprintf('%s.date', $alias), ':from'));
        $queryBuilder->andWhere($queryBuilder->expr()->lte(sprintf('%s.date', $alias), ':till'));
        $queryBuilder->setParameter('from', $from);
        $queryBuilder->setParameter('till', $till);
    }

    private function removeDateWhere(QueryBuilder $queryBuilder, string $resourceClass)
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere(sprintf('%s.date < now()', $rootAlias));
        /** @var Andx $whereParts */
        $whereParts = $queryBuilder->getDQLPart('where');
        $parameters = $queryBuilder->getParameters();
        $allowedParts = [];
        foreach ($whereParts->getParts() as $wherePart) {
            if (0 !== strpos($wherePart, sprintf('%s.date', $rootAlias))) {
                $allowedParts[] = $wherePart;
            } else {
                $parameterName = strstr($wherePart, ':');
                if ($parameterName) {
                    /** @var Parameter $parameter */
                    foreach ($parameters as $parameter) {
                        if ($parameter->getName() === substr($parameterName, 1)) {
                            $parameters->removeElement($parameter);
                            break;
                        }
                    }
                }
            }
        }

        $queryBuilder->resetDQLPart('where');
        $queryBuilder->add('where', new Andx($allowedParts));
    }
}
