<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Api\Dto\EmailVerify;
use App\Entity\User;
use App\Exception\Security\OTP\InvalidException;
use App\Security\OtpManager;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class EmailVerifyInputDataTransformer implements DataTransformerInterface
{
    /**
     * @var OtpManager
     */
    private $otpManager;

    /**
     * EmailVerifyInputDataTransformer constructor.
     *
     * @param OtpManager $otpManager
     */
    public function __construct(OtpManager $otpManager)
    {
        $this->otpManager = $otpManager;
    }

    /**
     * @param EmailVerify $data
     * @param string      $to
     * @param array       $context
     *
     * @return User|object
     */
    public function transform($data, string $to, array $context = [])
    {
        try {
            $this->otpManager->checkToken($data->email, $data->token);
        } catch (InvalidException $e) {
            $violations = new ConstraintViolationList(
                [
                    new ConstraintViolation(
                        $e->getMessage(),
                        null,
                        [],
                        $data,
                        'token',
                        $data->token
                    ),
                ]
            );

            throw new ValidationException($violations);
        }

        /** @var User $existingUser */
        $existingUser = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        $existingUser->setEmail($data->email);

        return $existingUser;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof User) {
            return false;
        }

        return User::class === $to && null !== ($context['input']['class'] ?? null);
    }
}
