<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "post"={
 *              "path"="recovery",
 *              "status"=204,
 *          },
 *          "get"={"access_control"="false", "deprecation_reason"="Only for client generator"},
 *     },
 *     itemOperations={},
 *     output=false,
 * )
 *
 * Class PasswordRecovery
 */
class PasswordRecovery
{
    /**
     * @var string user's username
     *
     * @Assert\NotBlank()
     */
    public $receptor;

    /**
     * @var string OTP token that send to user device
     *
     * @Assert\NotBlank()
     */
    public $token;

    /**
     * @var string new user password
     * @Assert\NotBlank()
     * @Assert\Length(min="6")
     */
    public $password;
}
