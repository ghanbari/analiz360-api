<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\SendOneTimePasswordAction;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     output=false,
 *     collectionOperations={
 *          "post"={
 *              "controller"=SendOneTimePasswordAction::class,
 *              "denormalization_context"={"groups"={"one_time_pass:write"}}
 *          },
 *          "get"={"access_control"="false", "deprecation_reason"="Only for client generator"},
 *     },
 *     itemOperations={}
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\OneTimePasswordRepository")
 * @ORM\EntityListeners({"App\Doctrine\EntityListener\OneTimePasswordListener"})
 */
class OneTimePassword
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column()
     *
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     *
     * @Groups({"one_time_pass:write"})
     */
    private $receptor;

    /**
     * @ORM\Column(type="string", length=10)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max="10")
     */
    private $token;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Assert\NotNull()
     * @Assert\DateTime()
     */
    private $requestedAt;

    /**
     * @ORM\Column(type="string", length=50)
     *
     * @Assert\Ip()
     * @Assert\NotBlank()
     */
    private $ip;

    /**
     * @ORM\Column(type="smallint")
     */
    private $tryCount;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\IsTrue()
     */
    private $isValid;

    /**
     * RegisterRequest constructor.
     */
    public function __construct()
    {
        $this->setToken(random_int(100000, 999999));
        $this->setRequestedAt(new \DateTime());
        $this->setIsValid(true);
        $this->tryCount = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getRequestedAt(): ?\DateTimeInterface
    {
        return $this->requestedAt;
    }

    public function setRequestedAt(\DateTimeInterface $requestedAt): self
    {
        $this->requestedAt = $requestedAt;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getTryCount(): ?int
    {
        return $this->tryCount;
    }

    public function increaseTryCount(): self
    {
        ++$this->tryCount;

        return $this;
    }

    public function getIsValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): self
    {
        $this->isValid = $isValid;

        return $this;
    }
}
