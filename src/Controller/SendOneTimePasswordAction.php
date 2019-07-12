<?php

namespace App\Controller;

use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Entity\OneTimePassword;
use App\Security\OtpManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendOneTimePasswordAction extends AbstractController
{
    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var array
     */
    private $configs;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var OtpManager
     */
    private $otpManager;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * SendOneTimePasswordAction constructor.
     *
     * @param OtpManager            $otpManager
     * @param ValidatorInterface    $validator
     * @param RegistryInterface     $doctrine
     * @param ParameterBagInterface $parameters
     * @param TranslatorInterface   $translator
     * @param RequestStack          $request
     */
    public function __construct(
        OtpManager $otpManager,
        ValidatorInterface $validator,
        RegistryInterface $doctrine,
        ParameterBagInterface $parameters,
        TranslatorInterface $translator,
        RequestStack $request
    ) {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->requestStack = $request;
        $this->otpManager = $otpManager;
        $this->validator = $validator;
        $this->configs = $parameters->get('registration');
    }

    /**
     * @param OneTimePassword $data
     *
     * @return JsonResponse
     *
     * @throws \Exception
     *
     * TODO: get another post params(strict) + add recaptcha
     * TODO: when strict is true, check if phone number is not exists, return error
     */
    public function __invoke(OneTimePassword $data)
    {
        $data->setIp($this->requestStack->getMasterRequest()->getClientIp());
        $this->validator->validate($data);

        $rec = $data->getReceptor();
        $type = strpos($rec, '@') ? 'email' : (is_numeric($rec) ? 'phone' : $this->configs['username']);
        $result = $this->otpManager->send($data, $type);

        if (is_int($result)) {
            $message = $this->translator->trans('Your token has been sent, please wait');

            return new JsonResponse(
                ['message' => $message, 'remainTime' => $result],
                JsonResponse::HTTP_BAD_REQUEST
            );
        } else {
            $this->doctrine->getManager()->persist($data);
            $this->doctrine->getManager()->flush();
        }

        $message = $this->translator->trans('Your token is send to %receptor%', ['%receptor%' => $data->getReceptor()]);

        return new JsonResponse($message, JsonResponse::HTTP_OK);
    }
}
