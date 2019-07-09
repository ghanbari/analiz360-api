<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Controller\AddEmailToOutboxAction;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "post"={
 *              "controller"=AddEmailToOutboxAction::class,
 *              "defaults"={"_api_persist"=false, "_api_respond"=false},
 *          },
 *          "get"={
 *              "normalization_context"={"groups"={"message:read", "template:name", "template"}},
 *          },
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
 *     normalizationContext={"groups"={"message:read", "template"}},
 *     denormalizationContext={"groups"={"message:write"}},
 * )
 *
 * @ApiFilter(DateFilter::class, properties={"time"})
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={"template.template": "ipartial", "receptor": "ipartial", "provider": "ipartial", "status": "exact"})
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"id", "template.name", "time", "receptor", "priority", "senderEmail", "status"}
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\EmailMessageRepository")
 */
class EmailMessage
{
    use TimestampableEntity;
    use BlameableEntity;

    const STATUS_PREPARE = 0; // Only save in db use null instead of this value
    const STATUS_IN_BUS = 5; // In Queue
    const STATUS_SENT = 10; // Was sent
    const STATUS_DELIVERED = 30; // Delivered smtp report
    const STATUS_FAILED = 35; // Can not deliver (smtp report)
    const STATUS_OPENED = 50; // User open email (my url callback was loaded)

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

//    private $subject; #title

    /**
     * @var EmailTemplate The template that will be used for message body
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\EmailTemplate")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Assert\NotNull()
     * @Assert\Type(type="App\Entity\EmailTemplate")
     *
     * @Groups({"template", "message:write"})
     */
    private $template;

    /**
     * @var array arguments that will be send to template parser
     *
     * @ORM\Column(type="json")
     *
     * @Assert\Type(type="array")
     *
     * @Groups({"message:read", "message:write"})
     */
    private $arguments = [];

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
     * @var array the users that message will be sent to them
     *
     * @Assert\Type("array")
     *
     * @Groups({"message:write"})
     */
    private $users = [];

    /**
     * @var array the emails address that message will be send to them
     *
     * @Assert\Type("array")
     * @Assert\All(
     *     {
     *          @Assert\Email(mode="strict")
     *     }
     * )
     *
     * @Groups({"message:write"})
     */
    private $receptors = [];

    /**
     * @var string The email address that message will be send to it
     *
     * @ORM\Column()
     *
     * @Groups({"message:read", "message:write", "message:update"})
     */
    private $receptor;

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
     * @var string the email address that will be set as sender
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     *
     * @Groups({"message:read", "message:write", "message:update"})
     */
    private $senderEmail;

//    private $channel #use channel instead of sender email. channel is relation with [address, name] fields.

    /**
     * @var ScheduledMessage which schedule created this message
     *
     * @ORM\ManyToOne(targetEntity="ScheduledMessage")
     *
     * @Groups({"schedule:read"})
     */
    private $scheduledMessage;

    /**
     * @ORM\Column(type="smallint")
     *
     * @Groups({"message:read"})
     */
    private $status;

//    private $statusMessage;

    /**
     * @ORM\Column(type="boolean")
     */
    private $sensitiveData;

    public function __construct()
    {
        $this->setPriority(0);
        $this->setStatus(self::STATUS_PREPARE);
        $this->setSensitiveData(false);
    }

    public function getMessage()
    {
        $placeholders = array_map(function ($parameter) { return sprintf('{%s}', $parameter); }, $this->getTemplate()->getParameters());

        return str_replace($placeholders, $this->getArguments(), $this->getTemplate()->getTemplate());
    }

    public function addUserAttributes(User $user)
    {
        $attributes = [
            'user_username' => strval($user->getUsername()),
            'user_password' => strval($user->getRawPassword()),
            'user_email' => strval($user->getEmail()),
            'user_phone' => strval($user->getPhone()),
            'user_firstName' => strval($user->getFirstName()),
            'user_lastName' => strval($user->getLastName()),
            'user_sex' => null === $user->getSex() ? '' : (User::SEX_MALE == $user->getSex() ? 'آقا' : 'خانم'),
            'user_city' => null !== $user->getCity() ? $user->getCity()->getName() : '',
        ];

        $this->setArguments(array_merge($this->getArguments(), $attributes));
    }

    public function canUpdate()
    {
        return $this->getTime() >= (new \DateTime())
            || in_array($this->getStatus(), [self::STATUS_BLOCKED, self::STATUS_DELIVERED, self::STATUS_UNDELIVERED]);
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

    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(?string $senderEmail): self
    {
        $this->senderEmail = $senderEmail;

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
     * @return EmailMessage
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

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
