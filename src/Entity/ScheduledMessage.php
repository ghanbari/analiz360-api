<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ScheduledMessageRepository")
 *
 * @ApiResource(
 *     denormalizationContext={"groups"={"schedule:write", "user:read:id"}},
 *     normalizationContext={"groups"={"schedule:read", "user:read", "media", "template:name"}}
 * )
 *
 * @ApiFilter(DateFilter::class, properties={"startAt": DateFilter::EXCLUDE_NULL, "expireAt": DateFilter::EXCLUDE_NULL})
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={"template.template": "ipartial", "message": "ipartial", "messageType": "exact", "dateType": "exact"})
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"id", "messageType", "startAt", "expireAt", "expired", "usageCount"}
 * )
 */
class ScheduledMessage
{
    use TimestampableEntity;
    use BlameableEntity;

    const DATE_TYPE_ABSOLUTE_DATE = 1;
    const DATE_TYPE_REPEATABLE_DATE = 2;
    const DATE_TYPE_USER_BIRTHDAY = 3;
    const DATE_TYPE_USER_REGISTRATION = 4;

    const MESSAGE_TYPE_EMAIL = 'email';
    const MESSAGE_TYPE_SMS = 'sms';

    /**
     * @var int scheduled sms id
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"schedule:read"})
     */
    private $id;

    /**
     * @var EmailTemplate The template that will be used for message body
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\EmailTemplate")
     *
     * @Assert\Expression("this.getMessageType() !== constant('\\App\\Entity\\ScheduledMessage::MESSAGE_TYPE_EMAIL') or value !== null")
     * @Assert\Type(type="App\Entity\EmailTemplate")
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $template;

    /**
     * @var array arguments that will be send to template parser
     *
     * @ORM\Column(type="json")
     *
     * @Assert\Type(type="array")
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $arguments = [];

    /**
     * @var string sms text
     *
     * @ORM\Column(type="string", length=500, nullable=true)
     *
     * @Assert\Expression("this.getMessageType() !== constant('\\App\\Entity\\ScheduledMessage::MESSAGE_TYPE_SMS') or value !== null")
     * @Assert\Length(max="500")
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $message;

    /**
     * @var int max times that this sms will be schedule, Null value means ultimate usage
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="numeric")
     * @Assert\Range(min="1")
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $maxUsageCount;

    /**
     * @var int the number of times this sms was scheduled
     *
     * @ORM\Column(type="integer")
     *
     * @Groups({"schedule:read"})
     */
    private $usageCount;

    /**
     * @var \DateTime this scheduled sms will be start at this time
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\DateTime()
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $startAt;

    /**
     * @var \DateTime this scheduled sms will be expire at this time
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\DateTime()
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $expireAt;

    /**
     * @var bool Whether this scheduled sms is expired or not. (not reliable value)
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $expired;

    /**
     * @var int Specify schedule date type(absolute, relative, birthday, registration)
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Choice(callback="getValidTypes")
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $dateType;

    /**
     * @var array specify times that this schedule will send message (json)
     *
     * @ORM\Column(type="json")
     *
     * @Assert\NotBlank()
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $dates = [];

    /**
     * @var ArrayCollection List of users that sms will be send to them. (null to send to all users)
     *
     * @ORM\ManyToMany(targetEntity="User", fetch="EAGER")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     *
     * @Groups({"schedule:read", "schedule:write", "user:read"})
     */
    private $users;

    /**
     * @var array list of users phone numbers that sms will be send to them
     *
     * @ORM\Column(type="json", nullable=true)
     *
     * @Assert\Type("array")
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $receptors = [];

    /**
     * @var int maximum frequency of sending messages in case of error
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Type(type="numeric")
     * @Assert\Range(min="1")
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $maxTryCount;

    /**
     * @var int If message status is not delivered or blocked after second, message will be send again
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Type(type="numeric")
     * @Assert\Range(min="1")
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $timeout;

    /**
     * @var int the priority of scheduled messages
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $priority;

    /**
     * @var string the provider name, if set message will be send with this provider
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $provider;

    /**
     * @var string the email address that will be set as sender
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     *
     * @Groups({"message:read", "message:write", "message:update"})
     */
    private $senderEmail;

    /**
     * @var string Message type can be email or sms
     *
     * @ORM\Column(type="string", length=10)
     *
     * @Assert\Choice(choices={ScheduledMessage::MESSAGE_TYPE_EMAIL, ScheduledMessage::MESSAGE_TYPE_SMS})
     *
     * @Groups({"schedule:read", "schedule:write"})
     */
    private $messageType;

    /**
     * @var \DateTime the last time that this template was used
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Groups({"schedule:read"})
     */
    private $lastUsageTime;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->setExpired(false);
        $this->setUsageCount(0);
        $this->setPriority(0);
        $this->setMaxTryCount(1);
        $this->setTimeout(60);
    }

    public static function getValidTypes()
    {
        return [
            self::DATE_TYPE_ABSOLUTE_DATE,
            self::DATE_TYPE_REPEATABLE_DATE,
            self::DATE_TYPE_USER_BIRTHDAY,
            self::DATE_TYPE_USER_REGISTRATION,
        ];
    }

    /**
     * @Assert\Callback()
     *
     * @param ExecutionContextInterface $context
     */
    public function isValidDate(ExecutionContextInterface $context)
    {
        if (self::DATE_TYPE_ABSOLUTE_DATE === $this->getDateType() && !array_key_exists('datetimes', $this->dates)) {
            $context->buildViolation('The value must be a json object that have array of dates. {datetimes: []}')
                ->atPath('dates')
                ->addViolation();
        } elseif (self::DATE_TYPE_USER_BIRTHDAY === $this->getDateType()) {
            if (!array_key_exists('days', $this->dates)) {
                $context->buildViolation('The value must have this format: {days: [{day: int, next: bool, time: time}]}')
                    ->atPath('dates')
                    ->addViolation();
            } else {
                foreach ($this->dates['days'] as $day) {
                    if (!array_key_exists('day', $day) || !array_key_exists('time', $day) || !array_key_exists('next', $day)) {
                        $context->buildViolation('The value must have this format: {days: [{day: int, next: bool, time: time}]}')
                            ->atPath('dates')
                            ->addViolation();
                    }
                }
            }
        } elseif (self::DATE_TYPE_USER_REGISTRATION === $this->getDateType()) {
            if (!array_key_exists('days', $this->dates)) {
                $context->buildViolation('The value must have this format: {days: [{day: int, time: time}]}')
                    ->atPath('dates')
                    ->addViolation();
            } else {
                foreach ($this->dates['days'] as $day) {
                    if (!array_key_exists('day', $day) || !array_key_exists('time', $day)) {
                        $context->buildViolation('The value must have this format: {days: [{day: int, time: time}]}')
                            ->atPath('dates')
                            ->addViolation();
                    }
                }
            }
        } elseif (self::DATE_TYPE_REPEATABLE_DATE === $this->getDateType()) {
            if ((!array_key_exists('weekday', $this->dates)
                    && !array_key_exists('monthday', $this->dates)
                    && !array_key_exists('daily', $this->dates))
                || !array_key_exists('time', $this->dates)
            ) {
                $context->buildViolation('The value must have this format:
                    {weekday: [sunday], time: time} || {monthday: [20, 25], time: time} || {daily: int, time: time}
                ')
                    ->atPath('dates')
                    ->addViolation();
            }
        }
    }

    /**
     * @param $receptor
     * @param \DateTime $time
     *
     * @return EmailMessage|SmsMessage|null
     */
    public function createMessage($receptor, \DateTime $time)
    {
        if (self::MESSAGE_TYPE_SMS === $this->getMessageType()) {
            $message = new SmsMessage();
            $message->setMessage($this->getMessage());
            $message->setMaxTryCount($this->getMaxTryCount());
            $message->setTimeout($this->getTimeout());
            $message->setProvider($this->getProvider());
        } elseif (self::MESSAGE_TYPE_EMAIL === $this->getMessageType()) {
            $message = new EmailMessage();
            $message->setTemplate($this->getTemplate());
            $message->setSenderEmail($this->getSenderEmail());
            $message->setArguments($this->getArguments());
        } else {
            return null;
        }

        $message->setTime($time);
        $message->setReceptor($receptor);
        $message->setPriority($this->getPriority());
        $message->setScheduledMessage($this);
        $message->setCreatedBy($this->getCreatedBy());

        return $message;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaxUsageCount(): ?int
    {
        return $this->maxUsageCount;
    }

    public function setMaxUsageCount(?int $maxUsageCount): self
    {
        $this->maxUsageCount = $maxUsageCount;

        return $this;
    }

    public function getUsageCount(): ?int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): self
    {
        $this->usageCount = $usageCount;

        return $this;
    }

    public function increaseUsageCount(): self
    {
        $this->setLastUsageTime(new \DateTime());

        ++$this->usageCount;

        return $this;
    }

    public function getStartAt(): ?\DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(?\DateTimeInterface $startAt): self
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getExpireAt(): ?\DateTimeInterface
    {
        return $this->expireAt;
    }

    public function setExpireAt(?\DateTimeInterface $expireAt): self
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    public function getExpired(): ?bool
    {
        return $this->expired;
    }

    public function setExpired(bool $expired): self
    {
        $this->expired = $expired;

        return $this;
    }

    public function getDateType(): ?int
    {
        return $this->dateType;
    }

    public function setDateType(int $dateType): self
    {
        $this->dateType = $dateType;

        return $this;
    }

    public function getDates(): ?array
    {
        return $this->dates;
    }

    public function setDates(array $dates): self
    {
        $this->dates = $dates;

        return $this;
    }

    public function getTemplate(): ?EmailTemplate
    {
        return $this->template;
    }

    public function setTemplate(?EmailTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getReceptors(): ?array
    {
        return $this->receptors;
    }

    public function setReceptors(?array $receptors): self
    {
        $this->receptors = $receptors;

        return $this;
    }

    public function getMaxTryCount(): ?int
    {
        return $this->maxTryCount;
    }

    public function setMaxTryCount(int $maxTryCount): self
    {
        $this->maxTryCount = $maxTryCount;

        return $this;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(?string $senderEmail): self
    {
        $this->senderEmail = $senderEmail;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
        }

        return $this;
    }

    public function getMessageType(): ?string
    {
        return $this->messageType;
    }

    public function setMessageType(string $messageType): self
    {
        $this->messageType = $messageType;

        return $this;
    }

    public function getLastUsageTime(): ?\DateTimeInterface
    {
        return $this->lastUsageTime;
    }

    public function setLastUsageTime(?\DateTimeInterface $lastUsageTime): self
    {
        $this->lastUsageTime = $lastUsageTime;

        return $this;
    }
}
