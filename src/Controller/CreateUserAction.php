<?php

namespace App\Controller;

use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\OtpManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CreateUserAction extends AbstractController
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var OtpManager
     */
    private $otpManager;

    /**
     * CreateUserAction constructor.
     *
     * @param OtpManager                   $otpManager
     * @param ValidatorInterface           $validator
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(
        OtpManager $otpManager,
        ValidatorInterface $validator,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->passwordEncoder = $passwordEncoder;
        $this->validator = $validator;
        $this->otpManager = $otpManager;
    }

    /**
     * @param User $data
     *
     * @return User
     *
     * @throws \Exception
     */
    public function __invoke(User $data): User
    {
        $this->validator->validate($data, ['groups' => ['User', $this->getUser() ? 'User:admin' : 'User:owner']]);

        /** @var UserRepository $repository */
        $repository = $this->getDoctrine()->getRepository('App:User');
        $user = $data;

        $config = $this->getParameter('registration');
        if ('phone' === $config['username']) {
            $user->setUsername($user->getPhone());
            $user->setEmail(null);
        } else {
            $user->setUsername($user->getEmail());
            $user->setPhone(null);
        }

        if ($refCode = $data->getReferrerCode()) {
            if ($referrer = $repository->findOneByCode($refCode)) {
                $user->setReferrer($referrer);
            }
        }

        if (!$this->getUser()) {
            $this->otpManager->checkToken($user->getUsername(), $user->getToken());
            $rawPassword = $user->getRawPassword();
        } else {
            $rawPassword = random_int(100000, 999999);
        }

        $password = $this->passwordEncoder->encodePassword($user, $rawPassword);
        $user->setRawPassword($rawPassword);
        $user->setPassword($password);

        do {
            $code = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 2)
                .substr(strtolower($user->getUsername()), -4);
            $isExists = count($repository->findByCode($code));
        } while (0 !== $isExists);

        $user->setCode($code);

        return $data;
    }
}
