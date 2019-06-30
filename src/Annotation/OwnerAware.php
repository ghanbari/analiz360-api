<?php

namespace App\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Annotation\Target("CLASS")
 */
final class OwnerAware
{
    /**
     * @var string the user field name
     */
    public $userFieldName;
}
