<?php

namespace App\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Gedmo\Mapping\Annotation\Blameable;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Security;

class BlameableSubscriber implements EventSubscriberInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * BlameableSubscriber constructor.
     *
     * @param Reader                    $reader
     * @param Security                  $security
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(Reader $reader, Security $security, PropertyAccessorInterface $propertyAccessor)
    {
        $this->reader = $reader;
        $this->security = $security;
        $this->propertyAccessor = $propertyAccessor;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['SetUser', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function SetUser(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $data = $request->attributes->get('data');
        $resourceClass = $request->attributes->get('_api_resource_class');
        $method = $request->getMethod();

        if (!$this->security->getToken()->isAuthenticated()
            || !$resourceClass
            || !class_exists($resourceClass)
            || !$data instanceof $resourceClass
            || !in_array($method, [Request::METHOD_POST, Request::METHOD_PUT])
        ) {
            return;
        }

        $classInfo = new \ReflectionClass($resourceClass);
        $properties = $classInfo->getProperties();
        $action = Request::METHOD_POST === $method ? 'create' : 'update';

        foreach ($properties as $property) {
            /** @var Blameable $blameable */
            $blameable = $this->reader->getPropertyAnnotation($property, Blameable::class);
            if ($blameable && $action === $blameable->on) {
                try {
                    $value = $this->propertyAccessor->getValue($data, $property->getName());
                    if (!$value) {
                        $this->propertyAccessor->setValue($data, $property->getName(), $this->security->getUser());
                    }
                } catch (InvalidArgumentException | AccessException | UnexpectedTypeException $e) {
                }
            }
        }
    }
}
