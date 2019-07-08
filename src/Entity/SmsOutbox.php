<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "get",
 *     },
 *     itemOperations={
 *          "get",
 *     },
 *     normalizationContext={"groups"={"sms_outbox:read"}},
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\SmsOutboxRepository")
 */
class SmsOutbox
{
    const STATUS_PREPARE = 0;
    const STATUS_IN_BUS = 5;
    const STATUS_IN_QUEUE = 10;
    const STATUS_SCHEDULED = 15;
    const STATUS_SEND_TO_TELECOMS = 20;
    const STATUS_UNDELIVERED = 25;
    const STATUS_DELIVERED = 30;
    const STATUS_BLOCKED = 35;

    use BlameableEntity;
    use TimestampableEntity;

    /**
     * @var int the outbox message id
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"sms_outbox:read"})
     */
    private $id;

    /**
     * @var SmsMessage the respective message
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\SmsMessage", inversedBy="smsOutboxes", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups({"sms_outbox:read"})
     */
    private $message;

    /**
     * @var int the message status
     *
     * @ORM\Column(type="smallint")
     *
     * @Groups({"sms_outbox:read"})
     */
    private $status;

    /**
     * @var \DateTime the time that message sent
     *
     * @ORM\Column(type="datetime")
     *
     * @Groups({"sms_outbox:read"})
     */
    private $sendTime;

    /**
     * @var string the message tracking code returned by provider
     *
     * @ORM\Column(type="string", length=25, nullable=true)
     *
     * @Groups({"sms_outbox:read"})
     */
    private $trackingCode;

    /**
     * @var int the message cost
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Groups({"sms_outbox:read"})
     */
    private $cost;

    /**
     * @var int the count that status is checked
     *
     * @ORM\Column(type="smallint")
     *
     * @Groups({"sms_outbox:read"})
     */
    private $statusCheckCount;

    /**
     * @var string the sender number
     *
     * @ORM\Column(type="string", length=50)
     *
     * @Groups({"sms_outbox:read"})
     */
    private $sender;

    /**
     * SmsOutbox constructor.
     */
    public function __construct()
    {
        $this->setStatusCheckCount(0);
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }

    public function setSender(string $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;
        if ($this->message) {
            $this->message->setStatus($status);
        }

        return $this;
    }

    public function getSendTime(): \DateTimeInterface
    {
        return $this->sendTime;
    }

    public function setSendTime(\DateTimeInterface $sendTime): self
    {
        $this->sendTime = $sendTime;

        return $this;
    }

    public function getTrackingCode(): ?string
    {
        return $this->trackingCode;
    }

    public function setTrackingCode(?string $trackingCode): self
    {
        $this->trackingCode = $trackingCode;

        return $this;
    }

    public function getCost(): ?int
    {
        return $this->cost;
    }

    public function setCost(?int $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    public function getStatusCheckCount(): ?int
    {
        return $this->statusCheckCount;
    }

    public function setStatusCheckCount(int $statusCheckCount): self
    {
        $this->statusCheckCount = $statusCheckCount;

        return $this;
    }

    public function getMessage(): ?SmsMessage
    {
        return $this->message;
    }

    public function setMessage(?SmsMessage $message): self
    {
        $this->message = $message;
        $this->message->setStatus($this->status);

        return $this;
    }

    public function increaseStatusCheckCount()
    {
        ++$this->statusCheckCount;

        return $this;
    }
}
