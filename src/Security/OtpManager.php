<?php

namespace App\Security;

use App\Entity\EmailMessage;
use App\Entity\OneTimePassword;
use App\Entity\SmsMessage;
use App\Exception\Security\OTP\InvalidException;
use App\Exception\Security\OTP\RequestLimitationException;
use App\Repository\OneTimePasswordRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class OtpManager extends AbstractController
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
     * @var OneTimePasswordRepository
     */
    private $repository;

    /**
     * @var MessageBusInterface
     */
    private $bus;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var Security
     */
    private $security;

    /**
     * SendOneTimePasswordAction constructor.
     *
     * @param Security              $security
     * @param RegistryInterface     $doctrine
     * @param ParameterBagInterface $parameters
     * @param TranslatorInterface   $translator
     * @param MessageBusInterface   $bus
     * @param RequestStack          $request
     */
    public function __construct(Security $security, RegistryInterface $doctrine, ParameterBagInterface $parameters, TranslatorInterface $translator, MessageBusInterface $bus, RequestStack $request)
    {
        $this->doctrine = $doctrine;
        $this->repository = $this->doctrine->getRepository('App:OneTimePassword');

        $this->bus = $bus;
        $this->requestStack = $request;

        $this->configs = $parameters->get('registration');
        $this->translator = $translator;
        $this->security = $security;
    }

    /**
     * @param OneTimePassword $data
     * @param $sendTo 'phone'|'email'
     *
     * @return bool
     *
     * @throws \Exception                 wrong config for otp limitation
     * @throws RequestLimitationException if user reach max allowed request
     */
    public function send(OneTimePassword $data, $sendTo)
    {
        if (empty($data->getReceptor()) or empty($data->getIp()) or empty($data->getToken())) {
            throw new \InvalidArgumentException('OTP Object is not valid');
        }

        $otp = $this->repository->getLast($data->getReceptor());
        if ($otp) {
            $ttl = $this->configs['otp']['ttl'];
            $remainTime = $ttl - (time() - $otp->getRequestedAt()->format('U'));
            if ($remainTime > 0) {
                return $remainTime;
            }
        }

        $this->checkPolicy($data);

        if ('phone' === $sendTo) {
            $message = new SmsMessage();
            $message->setType(SmsMessage::TYPE_OTP);
            $message->setSensitiveData(true);
            $message->setReceptor($data->getReceptor());
            $message->setTime(new \DateTime());
            $message->setPriority($this->configs['sms']['priority']);
            $message->setTimeout($this->configs['sms']['timeout']);
            $message->setMaxTryCount($this->configs['sms']['try_count']);
            $message->setMessage($this->translator->trans('Your temporary token is: %token%', ['%token%' => $data->getToken()]));
        } else {
            $template = $this->doctrine->getRepository('App:EmailTemplate')->findOneByName('otp');
            $message = new EmailMessage();
            $message->setSensitiveData(true);
            $message->setReceptor($data->getReceptor());
            $message->setTime(new \DateTime());
            $message->setPriority($this->configs['email']['priority']);
            $message->setSenderEmail($this->configs['email']['sender_email']);
            $message->setArguments(['token' => $data->getToken()]);
            $message->setTemplate($template);
        }

        try {
            $this->bus->dispatch($message);
        } catch (\Exception $e) {
            $manager = $this->doctrine->getManager();
            $manager->persist($message);
            $manager->flush($message);
        }

        return true;
    }

    /**
     * @param $receptor
     * @param $token
     *
     * @return bool
     *
     * @throws InvalidException
     */
    public function checkToken($receptor, $token)
    {
        $otp = $this->repository->getLast($receptor);
        if (!$otp) {
            throw new InvalidException($this->translator->trans('This temporary token is expired, please request again'));
        }

        if ($otp->getIp() !== $this->requestStack->getMasterRequest()->getClientIp()) {
            $otp->setIsValid(false);
            $this->doctrine->getManager()->flush($otp);
            throw new InvalidException($this->translator->trans('Your ip is changed and your register request expired, please try again'));
        }

        if ($otp->getToken() != $token) {
            $otp->increaseTryCount();
            if ($otp->getTryCount() >= $this->configs['otp']['allowed_guess']) {
                $otp->setIsValid(false);
                $exception = new InvalidException($this->translator->trans('This temporary token is expired, please register again'));
            } else {
                $exception = new InvalidException($this->translator->trans('Your temporary token is wrong'));
            }

            $this->doctrine->getManager()->flush($otp);
            throw $exception;
        } else {
            $otp->setIsValid(false);
            $this->doctrine->getManager()->flush($otp);
        }

        return true;
    }

    /**
     * @param OneTimePassword $otp
     *
     * @throws \Exception                 wrong config for otp limitation
     * @throws RequestLimitationException if user reach max allowed request
     */
    private function checkPolicy(OneTimePassword $otp)
    {
        if ($this->security->getToken()->isAuthenticated()) {
            preg_match('/Can try (\d+) times (?:from|in) (.+)/i', $this->configs['otp']['max_req']['authenticated_user'], $policy);
            if ($this->repository->getCountOfRequest(new \DateTime($policy[2]), $otp->getReceptor()) >= $policy[1]) {
                throw new RequestLimitationException($this->translator->trans('The number of your failed login exceeds the limit'));
            }

            return;
        }

        preg_match('/Can try (\d+) times (?:from|in) (.+)/i', $this->configs['otp']['max_req']['user_ip'], $policy);
        if ($this->repository->getCountOfRequest(new \DateTime($policy[2]), $otp->getReceptor(), $otp->getIp()) >= $policy[1]) {
            throw new RequestLimitationException($this->translator->trans('The number of your failed login exceeds the limit'));
        }

        preg_match('/Can try (\d+) times (?:from|in) (.+)/i', $this->configs['otp']['max_req']['user'], $policy);
        if ($this->repository->getCountOfRequest(new \DateTime($policy[2]), $otp->getReceptor()) >= $policy[1]) {
            throw new RequestLimitationException($this->translator->trans('The number of your failed login exceeds the limit'));
        }

        preg_match('/Can try (\d+) times (?:from|in) (.+)/i', $this->configs['otp']['max_req']['ip'], $policy);
        if ($this->repository->getCountOfRequest(new \DateTime($policy[2]), null, $otp->getIp()) >= $policy[1]) {
            throw new RequestLimitationException($this->translator->trans('The number of failed login From your IP exceeds the limit'));
        }
    }
}
