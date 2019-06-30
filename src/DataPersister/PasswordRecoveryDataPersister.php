<?php

namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Api\Dto\PasswordRecovery;
use App\Entity\User;
use App\Security\OtpManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordRecoveryDataPersister implements DataPersisterInterface
{
    /**
     * @var OtpManager
     */
    private $otpManager;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(OtpManager $otpManager, RegistryInterface $doctrine, UserPasswordEncoderInterface $passwordEncoder, ValidatorInterface $validator, TranslatorInterface $translator)
    {
        $this->otpManager = $otpManager;
        $this->doctrine = $doctrine;
        $this->passwordEncoder = $passwordEncoder;
        $this->validator = $validator;
        $this->translator = $translator;
    }

    public function supports($data): bool
    {
        return $data instanceof PasswordRecovery;
    }

    /**
     * @param PasswordRecovery $data
     *
     * @return PasswordRecovery
     */
    public function persist($data)
    {
        $this->validator->validate($data);

        $this->otpManager->checkToken($data->receptor, $data->token);

        /** @var User $user */
        $user = $this->doctrine->getRepository('App:User')->findOneByUsername($data->receptor);

        if (!$user) {
            $message = $this->translator->trans('User %username% is not exists.', ['%username%' => $data->receptor]);
            throw new NotFoundHttpException($message);
        }

        $password = $this->passwordEncoder->encodePassword($user, $data->password);
        $user->setRawPassword($password);
        $user->setPassword($password);
        $this->doctrine->getManager()->flush();

        return $data;
    }

    public function remove($data)
    {
        throw new \RuntimeException('"remove" is not supported');
    }
}
