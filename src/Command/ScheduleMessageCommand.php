<?php

namespace App\Command;

use App\Entity\ScheduledMessage;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleMessageCommand extends Command
{
    protected static $defaultName = 'app:sms-schedule';

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SmsTemplateCommand constructor.
     *
     * @param RegistryInterface $doctrine
     * @param LoggerInterface   $logger
     */
    public function __construct(RegistryInterface $doctrine, LoggerInterface $logger)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Parse sms templates and add sms to outbox.')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ScheduledMessage[] $scheduledMessages */
        $scheduledMessages = $this->doctrine->getRepository('App:ScheduledMessage')->getAll();
        foreach ($scheduledMessages as $scheduledMessage) {
            $this->logger->debug(sprintf('Parse scheduled sms: %d, type: %d', $scheduledMessage->getId(), $scheduledMessage->getDateType()));
            switch ($scheduledMessage->getDateType()) {
                case ScheduledMessage::DATE_TYPE_ABSOLUTE_DATE:
                    $this->parseGivenTemplate($scheduledMessage);
                    break;
                case ScheduledMessage::DATE_TYPE_REPEATABLE_DATE:
                    $this->parseRepeatableTemplate($scheduledMessage);
                    break;
                case ScheduledMessage::DATE_TYPE_USER_BIRTHDAY:
                    $this->parseBirthdayTemplate($scheduledMessage);
                    break;
            }

            $this->doctrine->getManager()->flush();
        }
    }

    /**
     * @param ScheduledMessage $message
     *
     * @return User[]|\Doctrine\Common\Collections\Collection|\Generator
     */
    private function getUsers(ScheduledMessage $message)
    {
        $users = $message->getUsers()->toArray();
        if (!empty($users)) {
            foreach ($users as $user) {
                yield $user;
            }

            return;
        }

        if (!empty($message->getReceptors())) {
            return;
        }

        $page = 0;
        do {
            $users = $this->doctrine->getRepository('App:User')->getAll(100, $page * 100);
            ++$page;
            foreach ($users as $user) {
                yield $user;
            }
        } while (!empty($users));
    }

    /**
     * @param ScheduledMessage $scheduledMessage
     * @param \DateTime        $date
     *
     * @return array
     */
    private function sendScheduledMessage(ScheduledMessage $scheduledMessage, \DateTime $date)
    {
        $messages = [];
        $this->logger->debug(sprintf('Scheduled Sms %d should send message.', $scheduledMessage->getId()));
        foreach ($this->getUsers($scheduledMessage) as $user) {
            if (ScheduledMessage::MESSAGE_TYPE_EMAIL === $scheduledMessage->getMessageType() && !empty($user->getEmail())) {
                $message = $scheduledMessage->createMessage($user->getEmail(), $date);
                $message->addUserAttributes($user);

                $this->doctrine->getManager()->persist($message);
                $messages[] = $message;

                $this->logger->debug(sprintf('Message will send to "%s" at %s', $user->getEmail(), $date->format('Y-m-d H:i:s O')));
            } elseif (ScheduledMessage::MESSAGE_TYPE_SMS === $scheduledMessage->getMessageType() && !empty($user->getPhone())) {
                $message = $scheduledMessage->createMessage($user->getPhone(), $date);
                $message->parseMessage($user);

                $this->doctrine->getManager()->persist($message);
                $messages[] = $message;

                $this->logger->debug(sprintf('Message will send to "%s" at %s', $user->getPhone(), $date->format('Y-m-d H:i:s O')));
            }
        }

        $receptors = $scheduledMessage->getReceptors();
        foreach ($receptors as $receptor) {
            $message = $scheduledMessage->createMessage($receptor, $date);
            if (ScheduledMessage::MESSAGE_TYPE_SMS === $scheduledMessage->getMessageType()) {
                $message->parseMessage();
            }

            $this->doctrine->getManager()->persist($message);
            $messages[] = $message;

            $this->logger->debug(sprintf('Message will send to "%s" at %s', $receptor, $date->format('Y-m-d H:i:s O')));
        }

        $scheduledMessage->increaseUsageCount();

        return $messages;
    }

    /**
     * @param ScheduledMessage $message
     *
     * @return array|void
     *
     * @throws \Exception
     */
    private function parseGivenTemplate(ScheduledMessage $message)
    {
        $dates = $message->getDates();

        if (!array_key_exists('datetimes', $dates)) {
            $this->logger->warning('wrong format for given message type', [$dates]);

            return;
        }

        $expire = true;
        $messages = [];
        foreach ($dates['datetimes'] as $datetime) {
            $today = new \DateTime('today');
            $date = new \DateTime($datetime);
            if ($date->format('Y-m-d') == $today->format('Y-m-d')) {
                $messages = array_merge_recursive($this->sendScheduledMessage($message, $date), $messages);
            }

            if ($date > $today) {
                $expire = false;
            }
        }

        if ($expire) {
            $this->logger->debug(sprintf('Scheduled Sms (%d) expired.', $message->getId()));
            $message->setExpired(true);
        }

        return $messages;
    }

    private function parseRepeatableTemplate(ScheduledMessage $message)
    {
        $dates = $message->getDates();

        if (!array_key_exists('weekday', $dates) && !array_key_exists('monthday', $dates) && !array_key_exists('daily', $dates)) {
            $this->logger->warning('wrong format for repeatable message type', [$dates]);

            return;
        }

        $messages = [];
        $time = (!array_key_exists('time', $dates) || !strtotime($dates['time'])) ? date('H:i:s') : $dates['time'];
        if (array_key_exists('weekday', $dates)) {
            foreach ($dates['weekday'] as $weekday) {
                try {
                    $today = new \DateTime('today');
                    $date = new \DateTime($weekday);
                    if ($date->format('Y-m-d') == $today->format('Y-m-d')) {
                        $messages = array_merge_recursive(
                            $this->sendScheduledMessage($message, new \DateTime(sprintf('%s %s', $weekday, $time))),
                            $messages
                        );
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Can not send message', [$e->getMessage(), $e->getTraceAsString()]);
                    continue;
                }
            }
        } elseif (array_key_exists('monthday', $dates)) {
            foreach ($dates['monthday'] as $monthday) {
                try {
                    if (date('j') == $monthday) {
                        $messages = array_merge_recursive(
                            $this->sendScheduledMessage($message, new \DateTime(sprintf('today %s', $time))),
                            $messages
                        );
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Can not send message', [$e->getMessage(), $e->getTraceAsString()]);
                    continue;
                }
            }
        } elseif (array_key_exists('daily', $dates)) {
            $today = new \DateTime('today');
            $start = $message->getStartAt() ?? $message->getCreatedAt();

            if ($message->getExpireAt()) {
                $period = new \DatePeriod($start, new \DateInterval(sprintf('P%dD', $dates['daily'])), $message->getExpireAt());
            } else {
                $period = new \DatePeriod($start, new \DateInterval(sprintf('P%dD', $dates['daily'])), $message->getMaxUsageCount());
            }

            /** @var \DateTime $occurrence */
            foreach ($period as $occurrence) {
                if ($occurrence->format('Y-m-d') == $today->format('Y-m-d')) {
                    $messages = array_merge_recursive(
                        $this->sendScheduledMessage($message, new \DateTime(sprintf('today %s', $time))),
                        $messages
                    );
                }
            }
        }

        return $messages;
    }

    /**
     * @param ScheduledMessage $scheduledMessage
     *
     * @return array|void
     *
     * @throws \Exception
     */
    private function parseBirthdayTemplate(ScheduledMessage $scheduledMessage)
    {
        $dates = $scheduledMessage->getDates();

        if (!array_key_exists('days', $dates)) {
            $this->logger->warning('wrong format for birthday message type', [$dates]);

            return;
        }

        $messages = [];
        foreach ($dates['days'] as $day) {
            if (!array_key_exists('day', $day)) {
                $this->logger->warning('wrong format for birthday message type', [$dates]);
                continue;
            }

            $time = (!array_key_exists('time', $day) || !strtotime($day['time'])) ? date('H:i:s') : $day['time'];
            $next = (array_key_exists('next', $day) && $day['next']);
            $date = new \DateTime(($next ? '+' : '-').$day['day'].' days');

            $criteria = ['birthday' => $date];

            $users = $scheduledMessage->getUsers()->toArray();
            if (!empty($users)) {
                $userIds = array_map(function ($user) {
                    return $user->getId();
                }, $users);
                $criteria['id'] = $userIds;
            }

            /** @var User[] $users */
            $users = $this->doctrine->getRepository('App:User')->findBy($criteria);
            foreach ($users as $user) {
                $today = new \DateTime('today');

                try {
                    $sendDate = $today >= $date ?
                        new \DateTime(sprintf('today %s', $time))
                        : new \DateTime(sprintf('%s %s', $date->format('Y-m-d'), $time));
                } catch (\Exception $e) {
                    $sendDate = $today >= $date ? $today : $date;
                }

                if (ScheduledMessage::MESSAGE_TYPE_EMAIL === $scheduledMessage->getMessageType() && !empty($user->getEmail())) {
                    $message = $scheduledMessage->createMessage($user->getEmail(), $sendDate);
                    $message->addUserAttributes($user);

                    $this->doctrine->getManager()->persist($message);
                    $messages[] = $message;

                    $this->logger->debug(sprintf('Message will send to "%s" at %s', $user->getEmail(), $sendDate->format('Y-m-d H:i:s O')));
                } elseif (ScheduledMessage::MESSAGE_TYPE_SMS === $scheduledMessage->getMessageType() && !empty($user->getPhone())) {
                    $message = $scheduledMessage->createMessage($user->getPhone(), $sendDate);
                    $message->parseMessage($user);

                    $this->doctrine->getManager()->persist($message);
                    $messages[] = $message;

                    $this->logger->debug(sprintf('Message will send to "%s" at %s', $user->getPhone(), $sendDate->format('Y-m-d H:i:s O')));
                } else {
                    $this->logger->error('Unknown message type', [$scheduledMessage->getMessageType()]);
                    continue;
                }

                $scheduledMessage->increaseUsageCount();
            }
        }

        return $messages;
    }
}
