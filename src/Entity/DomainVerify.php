<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"domainVerify:read"}},
 *          "denormalization_context"={"groups"={"domainVerify:write"}},
 *     },
 *     collectionOperations={
 *          "getToken"={
 *              "method"="post",
 *          },
 *          "checkToken"={
 *              "method"="post",
 *              "path"="domain_verifies/check",
 *          },
 *          "get"={"access_control"="false", "deprecation_reason"="Only for client generator"},
 *     },
 *     itemOperations={},
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\DomainVerifyRepository")
 * @ORM\Table(
 *     uniqueConstraints={@UniqueConstraint(name="UNIQUE_REQUEST", columns={"domain_id", "owner_id"})},
 *     uniqueConstraints={@UniqueConstraint(name="UNIQUE_REQUEST", columns={"domain_id", "secret"})},
 * )
 *
 * @UniqueEntity(fields={"domain", "owner"})
 * @ORM\HasLifecycleCallbacks()
 */
class DomainVerify
{
    use TimestampableEntity;

    /**
     * @var int id The db id
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Domain the domain that will be verify
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Domain")
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id", nullable=false, onDelete="RESTRICT")
     *
     * @Groups({"domainVerify:read", "domainVerify:write"})
     *
     * @Assert\NotNull()
     */
    private $domain;

    /**
     * @var User the user that will be owner of site
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=false)
     *
     * @Gedmo\Blameable(on="create")
     * @Gedmo\Blameable(on="update")
     */
    private $owner;

    /**
     * @ORM\Column(type="string", length=500)
     *
     * @Groups({"domainVerify:read"})
     */
    private $secret;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @ORM\PreFlush()
     */
    public function generateSecretCode()
    {
        if (is_null($this->secret)) {
            $code = base64_encode($this->domain).'.';
            $code .= base64_encode($this->owner->getUsername());
            $secret = hash_hmac('sha256', $code, getenv('APP_SECRET'));
            $this->setSecret($secret);
        }
    }
}
