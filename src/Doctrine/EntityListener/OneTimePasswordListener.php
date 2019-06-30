<?php

namespace App\Doctrine\EntityListener;

use App\Entity\OneTimePassword;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

class OneTimePasswordListener
{
    /**
     * @ORM\PrePersist()
     *
     * @param OneTimePassword    $oneTimePassword
     * @param LifecycleEventArgs $eventArgs
     */
    public function invalidatePrePasswords(OneTimePassword $oneTimePassword, LifecycleEventArgs $eventArgs)
    {
        $repository = $eventArgs->getEntityManager()->getRepository('App:OneTimePassword');
        $repository->invalidatePrePasswords($oneTimePassword);
    }
}
