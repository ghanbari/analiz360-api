<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Controller\AddSmsToOutboxAction;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "post"={
 *              "controller"=AddSmsToOutboxAction::class,
 *              "defaults"={"_api_persist"=false, "_api_respond"=false},
 *          },
 *          "get",
 *     },
 *     itemOperations={
 *          "get",
 *          "put"={
 *              "access_control"="previous_object.canUpdate() && is_granted('ROLE_ADMIN')",
 *              "access_control_message"="You can not update messages that have been sent or times are past.",
 *              "denormalization_context"={"groups"={"message:update"}}
 *          },
 *          "delete"={
 *              "access_control"="object.canUpdate()",
 *              "access_control_message"="You can not delete messages that have been sent or times are past.",
 *          },
 *     },
 *     normalizationContext={"groups"={"message:read"}},
 *     denormalizationContext={"groups"={"message:write"}},
 * )
 *
 * @ApiFilter(DateFilter::class, properties={"time"})
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={"message": "ipartial", "receptor": "ipartial", "provider": "ipartial", "status": "exact"})
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"id", "time", "receptor", "priority", "status", "maxTryCount", "timeout", "provider"}
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\SmsMessageRepository")
 * TODO: add url callback for status update by service provider
 */
class SmsMessage
{
    use TimestampableEntity;
    use BlameableEntity;

    /**
     * @var int message id
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"message:read"})
     */
    private $id;

    /**
     * @var \DateTime the time that message will be send
     *
     * @ORM\Column(type="datetime")
     *
     * @Assert\NotNull()
     * @Assert\Time()
     *
     * @Groups({"message:read", "message:write"})
     */
    private $time;

    /**
     * @var string message text
     *
     * @ORM\Column(type="string", length=500)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max="500")
     *
     * @Groups({"message:read", "message:write", "message:update"})
     */
    private $message;

    /**
     * @var array the users that message will be sent to them
     *
     * @Assert\Type("array")
     *
     * @Groups({"message:write"})
     */
    private $users = [];

    /**
     * @var array the phone numbers that sms will be send to them
     *
     * @Assert\Type("array")
     * @Assert\All(
     *     {
     *          @Assert\Type("numeric")
     *     }
     * )
     *
     * @Groups({"message:write"})
     */
    private $receptors = [];

    /**
     * @var string The phone number that message will be send to it
     *
     * @ORM\Column(type="string", length=12)
     *
     * @Groups({"message:read", "message:write", "message:update"})
     */
    private $receptor;

    /**
     * @var int maximum frequency of sending messages in case of error
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Type(type="numeric")
     * @Assert\Range(min="1")
     *
     * @Groups({"message:read", "message:write", "message:update"})
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
     * @Groups({"message:read", "message:write", "message:update"})
     */
    private $timeout;

    /**
     * @var int the priority of scheduled messages
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"message:read", "message:write", "message:update"})
     */
    private $priority;

    /**
     * @var string the provider name that will be usage for sending the message if it is exists
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     *
     * @Groups({"message:read", "message:write", "message:update"})
     */
    private $provider;

    /**
     * @var ScheduledMessage which schedule created this message
     *
     * @ORM\ManyToOne(targetEntity="ScheduledMessage")
     *
     * @Groups({"schedule:read"})
     */
    private $scheduledMessage;

    /**
     * @var ArrayCollection messages in outbox
     *
     * @ORM\OneToMany(targetEntity="App\Entity\SmsOutbox", mappedBy="message", orphanRemoval=true)
     *
     * @ApiSubresource()
     *
     * @Groups({"sms_outbox:read"})
     */
    private $smsOutboxes;

    /**
     * @ORM\Column(type="smallint")
     *
     * @Groups({"message:read"})
     */
    private $status;

    /**
     * @ORM\Column(type="boolean")
     */
    private $sensitiveData;

    public function __construct()
    {
        $this->smsOutboxes = new ArrayCollection();
        $this->setMaxTryCount(1);
        $this->setTimeout(60);
        $this->setPriority(0);
        $this->setStatus(SmsOutbox::STATUS_PREPARE);
        $this->setSensitiveData(false);
    }

    public function parseMessage(User $user = null)
    {
        $placeholders = [
            '{username}',
            '{password}',
            '{email}',
            '{phone}',
            '{firstName}',
            '{lastName}',
            '{sex}',
            '{city}',
        ];

        $values = [];
        if ($user) {
            $values = [
                strval($user->getUsername()),
                strval($user->getRawPassword()),
                strval($user->getEmail()),
                strval($user->getPhone()),
                strval($user->getFirstName()),
                strval($user->getLastName()),
                null === $user->getSex() ? '' : (User::SEX_MALE == $user->getSex() ? 'آقا' : 'خانم'),
                null !== $user->getCity() ? $user->getCity()->getName() : '',
            ];
        }

        $this->message = str_replace($placeholders, $values, $this->message);
    }

    public function canUpdate()
    {
        return $this->getTime() >= (new \DateTime())
            && !in_array($this->getStatus(), [SmsOutbox::STATUS_BLOCKED, SmsOutbox::STATUS_DELIVERED, SmsOutbox::STATUS_UNDELIVERED]);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): self
    {
        $this->time = $time;

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

    public function getReceptor(): ?string
    {
        return $this->receptor;
    }

    public function setReceptor(string $receptor): self
    {
        $this->receptor = $receptor;

        return $this;
    }

    public function getScheduledMessage(): ?ScheduledMessage
    {
        return $this->scheduledMessage;
    }

    public function setScheduledMessage(?ScheduledMessage $scheduledMessage): self
    {
        $this->scheduledMessage = $scheduledMessage;

        return $this;
    }

    /**
     * @return Collection|SmsOutbox[]
     */
    public function getSmsOutboxes(): Collection
    {
        return $this->smsOutboxes;
    }

    public function addSmsOutbox(SmsOutbox $smsOutbox): self
    {
        if (!$this->smsOutboxes->contains($smsOutbox)) {
            $this->setStatus($smsOutbox->getStatus());
            $this->smsOutboxes[] = $smsOutbox;
            $smsOutbox->setMessage($this);
        }

        return $this;
    }

    public function removeSmsOutbox(SmsOutbox $smsOutbox): self
    {
        if ($this->smsOutboxes->contains($smsOutbox)) {
            $this->smsOutboxes->removeElement($smsOutbox);
            // set the owning side to null (unless already changed)
            if ($smsOutbox->getMessage() === $this) {
                $smsOutbox->setMessage(null);
            }
        }

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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return SmsMessage
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        switch ($status) {
            case SmsOutbox::STATUS_BLOCKED:
            case SmsOutbox::STATUS_DELIVERED:
                $this->status = $status;
                break;
            case SmsOutbox::STATUS_IN_BUS:
            case SmsOutbox::STATUS_PREPARE:
                if (in_array($this->status, [SmsOutbox::STATUS_IN_BUS, SmsOutbox::STATUS_PREPARE])) {
                    $this->status = $status;
                }
                break;
            case SmsOutbox::STATUS_IN_QUEUE:
            case SmsOutbox::STATUS_SCHEDULED:
            case SmsOutbox::STATUS_SEND_TO_TELECOMS:
            case SmsOutbox::STATUS_UNDELIVERED:
                if (!in_array($this->status, [SmsOutbox::STATUS_BLOCKED, SmsOutbox::STATUS_DELIVERED])) {
                    $this->status = $status;
                }
                break;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getReceptors(): array
    {
        return $this->receptors;
    }

    /**
     * @param array $receptors
     */
    public function setReceptors(array $receptors): void
    {
        $this->receptors = $receptors;
    }

    /**
     * @return array
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @param array $users
     */
    public function setUsers(array $users): void
    {
        $this->users = $users;
    }

    public function hasSensitiveData(): ?bool
    {
        return $this->sensitiveData;
    }

    public function getSensitiveData(): ?bool
    {
        return $this->sensitiveData;
    }

    public function setSensitiveData(bool $sensitiveData): self
    {
        $this->sensitiveData = $sensitiveData;

        return $this;
    }
}
