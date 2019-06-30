<?php

namespace App\Exception\Wallet;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class LowCreditException extends AccessDeniedHttpException
{
    /**
     * LowCreditException constructor.
     */
    public function __construct()
    {
        parent::__construct('you dont have enough credit');
    }
}
