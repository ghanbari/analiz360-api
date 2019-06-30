<?php

namespace App\EventListener;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\DomainVerify;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class DomainVerifySubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Security
     */
    private $security;

    /**
     * DomainVerifySubscriber constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param Security               $security
     */
    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public static function getSubscribedEvents()
    {
        return [
           'kernel.view' => [
               ['checkDuplicate', EventPriorities::PRE_WRITE],
               ['checkToken', EventPriorities::PRE_WRITE],
           ],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function checkDuplicate(GetResponseForControllerResultEvent $event)
    {
        $newDomainVerify = $event->getControllerResult();
        $operation = $event->getRequest()->attributes->get('_api_collection_operation_name');

        if (!$newDomainVerify instanceof DomainVerify || 'getToken' !== $operation) {
            return;
        }

        $repo = $this->entityManager->getRepository('App:DomainVerify');
        $domainVerify = $repo->findOneBy(['domain' => $newDomainVerify->getDomain(), 'owner' => $this->security->getUser()]);

        $event->setControllerResult($domainVerify ?? $newDomainVerify);
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function checkToken(GetResponseForControllerResultEvent $event)
    {
        $newDomainVerify = $event->getControllerResult();
        $operation = $event->getRequest()->attributes->get('_api_collection_operation_name');

        if (!$newDomainVerify instanceof DomainVerify || 'checkToken' !== $operation) {
            return;
        }

        $domain = $newDomainVerify->getDomain();
        $repo = $this->entityManager->getRepository('App:DomainVerify');
        /** @var DomainVerify $domainVerify */
        $domainVerify = $repo->findOneBy(['domain' => $domain, 'owner' => $this->security->getUser()]);

        if (!$domainVerify) {
            $violations = new ConstraintViolationList(
                [
                    new ConstraintViolation(
                        'You dont have any verify request for this domain, you must send a request first.',
                        null,
                        [],
                        $domain->getDomain()->getDomain(),
                        'domain',
                        $domain->getDomain()->getDomain()
                    ),
                ]
            );

            throw new ValidationException($violations);
        }

        $path = sprintf('http://%s/analiz360.json', $domain->getDomain());
        try {
            $content = file_get_contents($path);
        } catch (\ErrorException $e) {
            throw new BadRequestHttpException(sprintf('%s is not found', $path));
        }

        $json = json_decode($content, true);

        if ($json['secret'] !== $domainVerify->getSecret()) {
            throw new BadRequestHttpException('file content is not equal with your secret');
        }

        $domain->setOwner($this->security->getUser());
        $this->entityManager->remove($domainVerify);
        $this->entityManager->flush();
        $event->setControllerResult(null);
    }
}
