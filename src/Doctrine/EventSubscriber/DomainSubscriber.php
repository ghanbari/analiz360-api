<?php

namespace App\Doctrine\EventSubscriber;

use App\Entity\Domain;
use App\Entity\Order;
use App\Entity\Product;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class DomainSubscriber implements EventSubscriber
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
     * DomainSubscriber constructor.
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
        $domain = $event->getEntity();
        $token = $this->security->getToken();

        if (!$domain instanceof Domain or !$token or !$token->isAuthenticated()) {
            return;
        }

        $this->addOrder($domain, $event->getEntityManager());
    }

    private function addOrder(Domain $domain, EntityManagerInterface $entityManager)
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        /** @var Product[] $products */
        $products = $entityManager->getRepository('App:Product')->findBy(['type' => Product::TYPE_ALEXA_ADD_DOMAIN]);

        if (!$products || count($products) > 1) {
            throw new BadRequestHttpException('This feature is temporary deactivate.');
        }

        $product = array_shift($products);
        $order = new Order($this->security->getUser(), $product, ['domain' => $domain->getDomain()]);
        $entityManager->persist($order);
    }
}
