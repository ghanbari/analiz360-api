<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;

class FilterConfigurator implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var Security
     */
    private $security;

    /**
     * UserFilterConfigurator constructor.
     *
     * @param ObjectManager $em
     * @param Security      $security
     * @param Reader        $reader
     */
    public function __construct(ObjectManager $em, Security $security, Reader $reader)
    {
        $this->em = $em;
        $this->reader = $reader;
        $this->security = $security;
    }

    public function userFilterConfig()
    {
        $user = $this->security->getUser();

        if ($user && $user instanceof User && !$this->security->isGranted('ROLE_ADMIN')) {
            $filter = $this->em->getFilters()->enable('user_filter');
            $filter->setParameter('id', $this->security->getUser()->getId());
            $filter->setAnnotationReader($this->reader);
        }
    }

    public function timeFilterConfig()
    {
        $user = $this->security->getUser();

        if ($user && $user instanceof User && !$this->security->isGranted('ROLE_ADMIN')) {
            $filter = $this->em->getFilters()->enable('time_filter');
            $filter->setAnnotationReader($this->reader);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => [
                ['userFilterConfig', 7],
                ['timeFilterConfig', 7],
            ],
        ];
    }
}
