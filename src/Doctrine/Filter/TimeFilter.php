<?php

namespace App\Doctrine\Filter;

use App\Annotation\TimeAware;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Common\Annotations\Reader;

class TimeFilter extends SQLFilter
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

        /** @var TimeAware $timeAware */
        $timeAware = $this->reader->getClassAnnotation($targetEntity->getReflectionClass(), TimeAware::class);

        if (!$timeAware) {
            return '';
        }

        $result = [];

        $visibleAfter = $timeAware->visibleAfter;
        $visibleStrictlyAfter = $timeAware->visibleStrictlyAfter;
        $visibleBefore = $timeAware->visibleBefore;
        $visibleStrictlyBefore = $timeAware->visibleStrictlyBefore;

        if (!empty($visibleAfter)) {
            $result[] = sprintf(
                '(%s.%s IS NULL OR %1$s.%2$s >= "%s")',
                $targetTableAlias,
                $targetEntity->getColumnName($visibleAfter),
                $this->getTime($targetEntity, $visibleAfter)
            );
        }

        if (!empty($visibleStrictlyAfter)) {
            $result[] = sprintf(
                '(%s.%s IS NULL OR %1$s.%2$s > "%s")',
                $targetTableAlias,
                $targetEntity->getColumnName($visibleStrictlyAfter),
                $this->getTime($targetEntity, $visibleStrictlyAfter)
            );
        }

        if (!empty($visibleBefore)) {
            $result[] = sprintf(
                '(%s.%s IS NULL OR %1$s.%2$s <= "%s")',
                $targetTableAlias,
                $targetEntity->getColumnName($visibleBefore),
                $this->getTime($targetEntity, $visibleBefore)
            );
        }

        if (!empty($visibleStrictlyBefore)) {
            $result[] = sprintf(
                '(%s.%s IS NULL OR %1$s.%2$s < "%s")',
                $targetTableAlias,
                $targetEntity->getColumnName($visibleStrictlyBefore),
                $this->getTime($targetEntity, $visibleStrictlyBefore)
            );
        }

        return join(' AND ', $result);
    }

    public function setAnnotationReader(Reader $reader)
    {
        $this->reader = $reader;
    }

    private function getTime(ClassMetaData $targetEntity, string $visibleAfter)
    {
        $now = new \DateTime();
        $fieldType = $targetEntity->getTypeOfField($visibleAfter);
        switch ($fieldType) {
            case 'datetime':
                return $now->format('Y-m-d H:i:s');
                break;
            case 'date':
                return $now->format('Y-m-d');
                break;
            case 'time':
                return $now->format('H:i:s');
                break;
        }
    }
}
