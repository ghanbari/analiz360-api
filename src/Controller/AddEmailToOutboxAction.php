<?php

namespace App\Controller;

use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Entity\EmailMessage;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class AddEmailToOutboxAction extends AbstractController
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * AddSmsToOutboxAction constructor.
     *
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function __invoke(Request $request, EmailMessage $data): array
    {
        $this->validator->validate($data);

        $manager = $this->getDoctrine()->getManager();
        $messages = [];

        if (!empty($data->getUsers())) {
            $usersIds = $data->getUsers();
            /** @var User[] $users */
            $users = $manager->getRepository('App:User')->findById($usersIds);
            foreach ($users as $user) {
                if (!empty($user->getEmail())) {
                    $message = clone $data;
                    $message->setReceptor($user->getEmail());
                    $message->addUserAttributes($user);
                    $manager->persist($message);
                    $messages[] = $message;
                }
            }
        }

        $receptors = $data->getReceptors();
        if ($data->getReceptor()) {
            $receptors[] = $data->getReceptor();
        }
        $receptors = array_unique($receptors);

        if (!empty($receptors)) {
            foreach ($receptors as $receptor) {
                if (!empty($receptor)) {
                    $message = clone $data;
                    $message->setReceptor($receptor);
                    $manager->persist($message);
                    $messages[] = $message;
                }
            }
        }

        if (empty($receptors) && empty($data->getUsers())) {
            $result = $manager->getRepository('App:EmailMessage')->sendMessageToAll($data);

            return $result;
        } else {
            $manager->flush();
        }

        return $messages;
    }
}
