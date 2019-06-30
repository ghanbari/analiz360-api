<?php

namespace App\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\EmailMessage;
use App\Entity\ScheduledMessage;
use App\Entity\SmsMessage;
use App\Entity\User;
use App\Repository\ScheduledMessageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Messenger\MessageBusInterface;

class UserSubscriber implements EventSubscriberInterface
{
    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var ScheduledMessageRepository
     */
    private $scheduledSmsRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UserSubscriber constructor.
     *
     * @param ScheduledMessageRepository $scheduledSmsRepository
     * @param ParameterBagInterface      $parameters
     * @param MessageBusInterface        $messageBus
     * @param RegistryInterface          $doctrine
     * @param LoggerInterface            $logger
     */
    public function __construct(
        ScheduledMessageRepository $scheduledSmsRepository,
        ParameterBagInterface $parameters,
        MessageBusInterface $messageBus,
        RegistryInterface $doctrine,
        LoggerInterface $logger
    ) {
        $this->parameters = $parameters;
        $this->messageBus = $messageBus;
        $this->doctrine = $doctrine;
        $this->scheduledSmsRepository = $scheduledSmsRepository;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
           'kernel.view' => [
               ['sendUserCredentials', EventPriorities::PRE_WRITE],
               ['completeRegistration', EventPriorities::POST_WRITE],
           ],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     *
     * @throws \Exception
     */
    public function sendUserCredentials(GetResponseForControllerResultEvent $event)
    {
        $user = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $manager = $this->doctrine->getManager();

        if (!$user instanceof User || Request::METHOD_POST !== $method) {
            return;
        }

        $registration = $this->parameters->get('registration');
        if ('phone' == $registration['username']) {
            $messageContent = 'کاربر گرامی، {sex} {firstName} {lastName}'.'\n';
            $messageContent .= 'نام کاربری شما: "{username}"'.'\n';
            $messageContent .= 'رمزعبور شما: "{password}"';

            $message = new SmsMessage();
            $message->setSensitiveData(true);
            $message->setTime(new \DateTime('now'));
            $message->setReceptor($user->getPhone());
            $message->setMessage($messageContent);
            $message->parseMessage($user);
            $message->setMaxTryCount($registration['sms']['try_count']);
            $message->setTimeout($registration['sms']['timeout']);
            $message->setPriority($registration['sms']['priority']);
        } else {
            $template = $this->doctrine->getRepository('App:EmailTemplate')->findOneByName('register');
            if (!$template) {
                $this->logger->error('registration email template is not exists');

                return;
            }

            $message = new EmailMessage();
            $message->setSensitiveData(true);
            $message->setTime(new \DateTime('now'));
            $message->setReceptor($user->getEmail());
            $message->setTemplate($template);
            $message->addUserAttributes($user);
            $message->setPriority($registration['email']['priority']);
            $message->setSenderEmail($registration['email']['sender_email']);
        }

        try {
            $this->messageBus->dispatch($message);
        } catch (\Exception $e) {
            $this->logger->error('Can not send message by Bus', [$e->getMessage(), $e->getTraceAsString()]);
            $manager->persist($message);
        }
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     *
     * @throws \Exception
     */
    public function completeRegistration(GetResponseForControllerResultEvent $event)
    {
        $user = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $manager = $this->doctrine->getManager();
        $outboxMessages = [];

        if (!$user instanceof User || Request::METHOD_POST !== $method) {
            return;
        }

        $templates = $this->scheduledSmsRepository->getAll();

        /** @var ScheduledMessage $template */
        foreach ($templates as $template) {
            $template->increaseUsageCount();
            if ($template->getMaxUsageCount() > 0 && $template->getUsageCount() > $template->getMaxUsageCount()) {
                $template->setExpired(true);
                continue;
            }

            $dates = $template->getDates();
            if (!array_key_exists('days', $dates)) {
                $this->logger->warning('wrong format for birthday message type', [$dates]);

                return;
            }

            foreach ($dates['days'] as $day) {
                if (!array_key_exists('day', $day) || !is_numeric($day['day'])) {
                    $this->logger->warning('wrong format for birthday message type', [$dates]);
                    continue;
                }

                $time = (!array_key_exists('time', $day) || !strtotime($day['time'])) ? date('H:i:s') : $day['time'];
                $date = new \DateTime(sprintf('+%d days %s', $day['day'], $time));

                if (ScheduledMessage::MESSAGE_TYPE_SMS === $template->getMessageType()) {
                    $message = $template->createMessage($user->getPhone(), $date);
                    $message->parseMessage($user);
                } elseif (ScheduledMessage::MESSAGE_TYPE_EMAIL === $template->getMessageType()) {
                    $message = $template->createMessage($user->getEmail(), $date);
                    $message->addUserAttributes($user);
                } else {
                    $this->logger->warning('wrong message type', [$template]);
                    continue;
                }

                if (0 == $day['day'] && (!array_key_exists('time', $day) || empty($day['time']))) {
                    try {
                        $this->messageBus->dispatch($message);
                    } catch (\Exception $e) {
                        $this->logger->error('Can not send message by Bus', [$e->getMessage(), $e->getTraceAsString()]);
                        $outboxMessages[] = $message;
                        $manager->persist($message);
                    }
                } else {
                    $outboxMessages[] = $message;
                    $manager->persist($message);
                }
            }
        }

        $manager->flush($outboxMessages);
    }
}
