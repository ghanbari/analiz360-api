<?php

namespace App\Doctrine\Filter;

use App\Annotation\OwnerAware;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Common\Annotations\Reader;

class UserFilter extends SQLFilter
{
    /**
     * @var Reader
     */
    protected $reader;

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (empty($this->reader)) {
            return '';
        }

        $OwnerAware = $this->reader->getClassAnnotation($targetEntity->getReflectionClass(), OwnerAware::class);

        if (!$OwnerAware) {
            return '';
        }

        $fieldName = $OwnerAware->userFieldName;
        $columnName = $targetEntity->getSingleAssociationJoinColumnName($fieldName);

        try {
            $userId = $this->getParameter('id');
        } catch (\InvalidArgumentException $e) {
            return '';
        }

        if (empty($fieldName) || empty($userId)) {
            return '';
        }

        $query = sprintf('%s.%s = %s', $targetTableAlias, $columnName, $userId);

        return $query;
    }

    public function setAnnotationReader(Reader $reader)
    {
        $this->reader = $reader;
    }
}
