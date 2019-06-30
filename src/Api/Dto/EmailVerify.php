<?php

namespace App\Api\Dto;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class EmailVerify.
 */
class EmailVerify
{
    /**
     * @var string user's username
     *
     * @Assert\NotBlank()
     *
     * @Groups("user:update:email")
     */
    public $email;

    /**
     * @var string OTP token that send to user device
     *
     * @Assert\NotBlank()
     *
     * @Groups("user:update:email")
     */
    public $token;
}
