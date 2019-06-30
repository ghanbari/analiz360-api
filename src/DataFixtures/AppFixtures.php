<?php

namespace App\DataFixtures;

use App\Entity\EmailTemplate;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $encoder;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * AppFixtures constructor.
     *
     * @param UserPasswordEncoderInterface $encoder
     * @param ParameterBagInterface        $parameterBag
     */
    public function __construct(UserPasswordEncoderInterface $encoder, ParameterBagInterface $parameterBag)
    {
        $this->encoder = $encoder;
        $this->parameterBag = $parameterBag;
    }

    public function load(ObjectManager $manager)
    {
        $this->createAdmin($manager);
        $this->createRegistrationEmailTemplate($manager);
        $this->createOtpEmailTemplate($manager);
        $this->addProduct($manager);

        $manager->flush();
    }

    private function createAdmin(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername(getenv('APP_ADMIN_USERNAME'));
        $user->setPassword($this->encoder->encodePassword($user, getenv('APP_ADMIN_PASSWORD')));
        $user->addRole('ROLE_ADMIN');
        $user->setEmail(getenv('APP_ADMIN_EMAIL'));
        $user->setPhone(getenv('APP_ADMIN_PHONE'));
        $user->setFirstName(getenv('APP_ADMIN_FIRST_NAME'));
        $user->setLastName(getenv('APP_ADMIN_LAST_NAME'));

        $manager->persist($user);
    }

    private function createRegistrationEmailTemplate(ObjectManager $manager)
    {
        $messageContent = 'کاربر گرامی، {sex} {firstName} {lastName}'.'\n';
        $messageContent .= 'نام کاربری شما: {username}'.'\n';
        $messageContent .= 'رمزعبور شما {password}';

        $template = new EmailTemplate('register', $messageContent, true);
        $manager->persist($template);
    }

    private function createOtpEmailTemplate(ObjectManager $manager)
    {
        $messageContent = 'شناسه امنیتی شما: {token}';

        $template = new EmailTemplate('otp', $messageContent, true);
        $manager->persist($template);
    }

    private function addProduct(ObjectManager $manager)
    {
        $addDomain = new Product(
            5,
            Product::TYPE_ALEXA_ADD_DOMAIN,
            'ثبت دامنه',
            ['history' => 3, 'duration' => 10]
        );

        $manager->persist($addDomain);
    }
}
