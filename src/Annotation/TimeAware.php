<?php

namespace App\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Annotation\Target("CLASS")
 */
final class TimeAware
{
    /**
     * @var string entity is visible after this field value
     */
    public $visibleAfter;

    /**
     * @var string entity is visible before this field value
     */
    public $visibleBefore;

    /**
     * @var string entity is visible strictly after this field value (not include field value)
     */
    public $visibleStrictlyAfter;

    /**
     * @var string entity is visible strictly before this field value (not include field value)
     */
    public $visibleStrictlyBefore;
}
