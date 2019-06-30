<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Domain;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class FindDomainByAddressDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @var RegistryInterface
     */
    private $doctrine;
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RegistryInterface $doctrine, RequestStack $requestStack)
    {
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Domain::class === $resourceClass && 'findByDomain' === $operationName;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        $request = $this->requestStack->getMasterRequest();

        return $this->doctrine->getRepository('App:Domain')->findOneByDomain($request->attributes->get('id', ''));
    }
}
