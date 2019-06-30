<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Controller\CreateUserAction;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Api\Dto\EmailVerify;

/**
 * @ApiResource(
 *     mercure="users/object.id",
 *     attributes={
 *          "normalization_context"={"groups"={"user:read", "media:read"}},
 *          "denormalization_context"={"groups"={}},
 *     },
 *     collectionOperations={
 *          "post"={
 *              "method"="post",
 *              "controller"=CreateUserAction::class,
 *              "denormalization_context"={"groups"={}},
 *              "validation_groups"={"User", "User:admin", "User:auto"},
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          },
 *          "register"={
 *              "method"="post",
 *              "path"="register",
 *              "controller"=CreateUserAction::class,
 *              "denormalization_context"={"groups"={"user:write:by-owner"}},
 *              "validation_groups"={"User", "User:owner", "User:auto"},
 *          },
 *          "get"={
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          },
 *     },
 *     itemOperations={
 *          "get"={
 *              "normalization_context"={"groups"={"user:read", "media:full-path"}},
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          },
 *          "profile"={
 *              "method"="get",
 *              "path"="profile",
 *              "defaults"={"id"=""},
 *              "normalization_context"={"groups"={"user:read", "media:full-path"}}
 *          },
 *          "profile_update"={
 *              "method"="put",
 *              "path"="profile",
 *              "defaults"={"id"=""},
 *              "normalization_context"={"groups"={"user:read", "media:full-path"}},
 *              "denormalization_context"={"groups"={"user:update"}},
 *              "validation_groups"={"User", "User:auto"},
 *              "access_control"="previous_object === user",
 *              "access_control_message"="You can update your account only.",
 *          },
 *          "email_update"={
 *              "method"="put",
 *              "path"="profile/email/verify",
 *              "defaults"={"id"=""},
 *              "input"=EmailVerify::class,
 *              "normalization_context"={"groups"={"user:read", "media:full-path"}},
 *              "denormalization_context"={"groups"={"user:update:email"}},
 *              "validation_groups"={"User", "User:auto"},
 *          },
 *          "put"={
 *              "normalization_context"={"groups"={"user:read", "media:full-path"}},
 *              "denormalization_context"={"groups"={"user:update", "user:update:by-admin"}},
 *              "validation_groups"={"User", "User:admin", "User:auto"},
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          },
 *     }
 * )
 *
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={"firstName": "ipartial", "lastName": "ipartial", "nationalNumber": "ipartial", "zipCode": "ipartial", "username": "ipartial"})
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"id", "firstName", "lastName", "username", "createdAt", "status", "email", "phone", "zipCode", "telephone", "nationalNumber"}
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 *
 * @UniqueEntity(fields={"username"}, message="user is exists")
 * @UniqueEntity(fields={"email"}, message="email is exists", ignoreNull=true)
 * @UniqueEntity(fields={"phone"}, message="phone is exists", ignoreNull=true)
 */
class User implements UserInterface
{
    use TimestampableEntity;
    use BlameableEntity;

    const SEX_MALE = 'm';
    const SEX_FEMALE = 'f';

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"user:read", "user:read:id"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, unique=true)
     *
     * @Groups({"user:read", "user:write:auto"})
     *
     * @Assert\NotBlank(groups={"User:auto"})
     * @Assert\Length(min="7", groups={"User:auto"})
     */
    private $username;

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     *
     * @Groups({"user:write:auto"})
     *
     * @Assert\NotBlank(groups={"User:auto"})
     */
    private $password;

    /**
     * @ORM\Column(type="json")
     *
     * @Assert\Type("array", groups={"User"})
     *
     * @Groups({"user:read"})
     */
    private $roles = [];

    /**
     * @var string
     *
     * @ORM\Column(nullable=true, unique=true)
     *
     * @Assert\Email(mode="strict", groups={"User"})
     * @Assert\Length(min="7", max="255", groups={"User"})
     * TODO: must be unique & verify before set
     *
     * @Groups({"user:read", "user:write:by-owner", "user:write:by-admin"})
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=12, nullable=true, unique=true)
     *
     * @Assert\Length(min="11", max="11", groups={"User"})
     * @Assert\Type("numeric", groups={"User"})
     * TODO: must be unique & verify before set
     *
     * @Groups({"user:read", "user:write:by-owner", "user:write:by-admin"})
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Groups({"user:read", "user:write:by-owner", "user:write:by-admin", "user:name", "user:update:by-owner", "user:update:by-admin"})
     *
     * @Assert\NotBlank(groups={"User"})
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Groups({"user:read", "user:write:by-owner", "user:write:by-admin", "user:name", "user:update:by-owner", "user:update:by-admin"})
     *
     * @Assert\NotBlank(groups={"User"})
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     *
     * @Groups({"user:read", "user:write:by-owner", "user:write:by-admin", "user:update:by-owner", "user:update:by-admin"})
     *
     * @Assert\Choice(choices={User::SEX_MALE, User::SEX_FEMALE}, groups={"User"})
     */
    private $sex;

    /**
     * @ORM\ManyToOne(targetEntity="City")
     *
     * @Assert\Type(type="App\Entity\City", groups={"User"})
     *
     * @Groups({"user:read", "user:write:by-owner", "user:write:by-admin", "user:update:by-owner", "user:update:by-admin"})
     */
    private $city;

    /**
     * @ORM\Column(type="date", nullable=true)
     *
     * @Assert\Date(groups={"User"})
     *
     * @Groups({"user:read", "user:write:by-owner", "user:write:by-admin", "user:update:by-owner", "user:update:by-admin"})
     */
    private $birthday;

    /**
     * @var string raw password
     *
     * @Assert\NotBlank(groups={"User:owner"})
     * @Assert\Length(min="6")
     *
     * @Groups({"user:write:by-owner"})
     */
    private $rawPassword;

    /**
     * @ORM\Column(type="smallint")
     *
     * @Assert\NotNull(groups={"User"})
     *
     * @Groups({"user:read", "user:write:by-admin", "user:update:by-admin"})
     */
    private $status;

    /**
     * @var string the user referrer code
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     *
     * @Assert\Length(max="100", groups={"User"})
     * @Assert\NotNull(groups={"User:auto"})
     *
     * @Groups({"user:read", "user:write:by-admin", "user:update:by-admin"})
     */
    private $code;

    /**
     * @var User the referrer user of this user
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     *
     * @Assert\Type(type="App\Entity\User", groups={"User"})
     *
     * @Groups({"referrer"})
     */
    private $referrer;

    /**
     * @var string the user's code that refer this user
     *
     * @Assert\Length(max="100", groups={"User"})
     *
     * @Groups("user:write:by-owner")
     */
    private $referrerCode;

    /**
     * @var string registration token that was send to phone or email
     *
     * @deprecated
     *
     * @Assert\NotBlank(groups={"User:owner"})
     *
     * @Groups("user:write:by-owner")
     */
    private $token;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Length(max="5000")
     *
     * @Groups({"user:read:by-admin", "user:write:by-admin", "user:update:by-admin"})
     */
    private $description;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(type="datetime")
     *
     * @Groups({"user:read"})
     */
    protected $createdAt;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default": 0})
     *
     * @Groups({"user:read"})
     */
    private $credit;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Domain", mappedBy="owner")
     */
    private $domains;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DomainWatcher", mappedBy="watcher")
     */
    private $domainWatchers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Order", mappedBy="user")
     */
    private $orders;

    /**
     * @Assert\Callback(groups={"User"})
     *
     * @param ExecutionContextInterface $context
     */
    public function isUsernameValid(ExecutionContextInterface $context)
    {
        if ('phone' === getenv('APP_REGISTRATION_TYPE')) {
            if (empty($this->phone)) {
                $context->buildViolation('This value should not be blank.')
                    ->atPath('phone')
                    ->addViolation();
            }
        } else {
            if (empty($this->email)) {
                $context->buildViolation('This value should not be blank.')
                    ->atPath('email')
                    ->addViolation();
            }
        }
    }

    public function __construct()
    {
        $this->status = self::STATUS_ACTIVE;
        $this->credit = 0;
        $this->domains = new ArrayCollection();
        $this->domainWatchers = new ArrayCollection();
        $this->orders = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getRawPassword(): ? string
    {
        return $this->rawPassword;
    }

    /**
     * @param string $rawPassword
     */
    public function setRawPassword(string $rawPassword): void
    {
        $this->rawPassword = $rawPassword;
    }

    public function getSalt()
    {
    }

    public function eraseCredentials()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getRoles(): ?array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return $roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRole($role): self
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function hasRole($role): bool
    {
        return in_array($role, $this->roles);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function setSex(?string $sex): self
    {
        $this->sex = $sex;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getReferrer(): ?self
    {
        return $this->referrer;
    }

    public function setReferrer(?self $referrer): self
    {
        $this->referrer = $referrer;

        return $this;
    }

    /**
     * @return string
     *
     * @deprecated
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @deprecated
     */
    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getReferrerCode(): ?string
    {
        return $this->referrerCode;
    }

    /**
     * @param string $referrerCode
     */
    public function setReferrerCode(?string $referrerCode = null): void
    {
        $this->referrerCode = $referrerCode;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCredit(): ?int
    {
        return $this->credit;
    }

    public function setCredit(int $credit): self
    {
        $this->credit = $credit;

        return $this;
    }

    /**
     * @return Collection|Domain[]
     */
    public function getDomains(): Collection
    {
        return $this->domains;
    }

    public function addDomain(Domain $domain): self
    {
        if (!$this->domains->contains($domain)) {
            $this->domains[] = $domain;
            $domain->setOwner($this);
        }

        return $this;
    }

    public function removeDomain(Domain $domain): self
    {
        if ($this->domains->contains($domain)) {
            $this->domains->removeElement($domain);
            // set the owning side to null (unless already changed)
            if ($domain->getOwner() === $this) {
                $domain->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DomainWatcher[]
     */
    public function getDomainWatchers(): Collection
    {
        return $this->domainWatchers;
    }

    public function addDomainWatcher(DomainWatcher $domainWatcher): self
    {
        if (!$this->domainWatchers->contains($domainWatcher)) {
            $this->domainWatchers[] = $domainWatcher;
            $domainWatcher->setWatcher($this);
        }

        return $this;
    }

    public function removeDomainWatcher(DomainWatcher $domainWatcher): self
    {
        if ($this->domainWatchers->contains($domainWatcher)) {
            $this->domainWatchers->removeElement($domainWatcher);
            // set the owning side to null (unless already changed)
            if ($domainWatcher->getWatcher() === $this) {
                $domainWatcher->setWatcher(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Order[]
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setUser($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->contains($order)) {
            $this->orders->removeElement($order);
            // set the owning side to null (unless already changed)
            if ($order->getUser() === $this) {
                $order->setUser(null);
            }
        }

        return $this;
    }
}
