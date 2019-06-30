<?php

namespace App\Exception\Security\OTP;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvalidException extends BadRequestHttpException
{
    /**
     * RequestLimitationException constructor.
     *
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
