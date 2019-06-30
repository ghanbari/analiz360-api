<?php

namespace App\Serializer;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use App\Annotation\OwnerAware;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

final class ContextBuilder implements SerializerContextBuilderInterface
{
    /**
     * @var SerializerContextBuilderInterface
     */
    private $decorated;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var CamelCaseToSnakeCaseNameConverter
     */
    private $nameConverter;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * ContextBuilder constructor.
     *
     * @param SerializerContextBuilderInterface $decorated
     * @param CamelCaseToSnakeCaseNameConverter $nameConverter
     * @param Security                          $security
     * @param Reader                            $reader
     * @param PropertyAccessorInterface         $propertyAccessor
     */
    public function __construct(SerializerContextBuilderInterface $decorated, CamelCaseToSnakeCaseNameConverter $nameConverter, Security $security, Reader $reader, PropertyAccessorInterface $propertyAccessor)
    {
        $this->decorated = $decorated;
        $this->nameConverter = $nameConverter;
        $this->reader = $reader;
        $this->propertyAccessor = $propertyAccessor;
        $this->security = $security;
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        if (!isset($context['groups'])) {
            return $context;
        }

        $resourceClass = $context['resource_class'] ?? null;
        if (!$resourceClass) {
            return $context;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $class = $this->nameConverter->normalize(substr($resourceClass, strrpos($resourceClass, '\\') + 1));
            $method = strtolower($request->getMethod());
            $operation = $normalization ? 'read' : ('post' === $method ? 'write' : ('put' === $method ? 'update' : 'delete'));
            $context['groups'][] = $class.':'.$operation.':by-admin';
        }

        /** @var OwnerAware $ownerAware */
        $ownerAware = $this->reader->getClassAnnotation(new \ReflectionClass($resourceClass), OwnerAware::class);
        $data = $request->attributes->get('data', null);
        if ($ownerAware && $ownerAware->userFieldName && $data instanceof $resourceClass
            && $this->propertyAccessor->getValue($request->attributes->get('data'), $ownerAware->userFieldName) === $this->security->getUser()
        ) {
            $class = $this->nameConverter->normalize(substr($resourceClass, strrpos($resourceClass, '\\') + 1));
            $method = strtolower($request->getMethod());
            $operation = $normalization ? 'read' : ('post' === $method ? 'write' : ('put' === $method ? 'update' : 'delete'));
            $context['groups'][] = $class.':'.$operation.':by-owner';
        }

        return $context;
    }
}
