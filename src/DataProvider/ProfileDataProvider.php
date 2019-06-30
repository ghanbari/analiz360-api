<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\User;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class ProfileDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var User
     */
    private $user;

    public function __construct(RegistryInterface $doctrine, TokenStorageInterface $token)
    {
        $this->doctrine = $doctrine;
        $this->user = $token->getToken()->getUser();
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return User::class === $resourceClass && in_array($operationName, ['profile_update', 'email_update', 'profile']);
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        if ($this->user instanceof User) {
            return $this->doctrine->getRepository('App:User')->find($this->user->getId());
        }
    }
}
