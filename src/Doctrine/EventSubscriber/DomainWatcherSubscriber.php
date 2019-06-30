<?php

namespace App\Doctrine\EventSubscriber;

use App\Entity\DomainWatcher;
use App\Repository\DomainWatcherRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class DomainWatcherSubscriber implements EventSubscriber
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Security
     */
    private $security;

    /**
     * WalletSubscriber constructor.
     *
     * @param Security            $security
     * @param TranslatorInterface $translator
     */
    public function __construct(Security $security, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->security = $security;
    }

    public function getSubscribedEvents()
    {
        return ['prePersist'];
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $domainWatcher = $event->getEntity();

        if (!$domainWatcher instanceof DomainWatcher) {
            return;
        }

        $this->expireOldPlan($domainWatcher, $event->getEntityManager());
    }

    private function expireOldPlan(DomainWatcher $domainWatcher, EntityManager $entityManager)
    {
        /** @var DomainWatcherRepository $repo */
        $repo = $entityManager->getRepository('App:DomainWatcher');

        /** @var DomainWatcher[] $plans */
        $plans = $repo->getActivePlans($domainWatcher->getDomain()->getId(), $domainWatcher->getWatcher()->getId());
        foreach ($plans as $plan) {
            $plan->setExpireAt(new \DateTime());
        }
    }
}
