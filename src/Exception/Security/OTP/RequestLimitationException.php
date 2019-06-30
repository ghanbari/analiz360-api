<?php

namespace App\Exception\Security\OTP;

use Symfony\Component\HttpKernel\Exception\HttpException;

class RequestLimitationException extends HttpException
{
    /**
     * RequestLimitationException constructor.
     *
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct(403, $message);
    }
}
