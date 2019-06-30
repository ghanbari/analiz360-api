<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * TODO: itemOperation get has limitation for users that has not ROLE_ADMIN(he must get plan(x day) for given domain by liz)
 * TODO: if he doesnt has plan for given domain then 403(handle by event: PRE_RESPOND).
 *
 * TODO: itemOperation put, user is owner or has ROLE_ADMIN, only admin can change status of domain
 *
 * TODO: collection operation post, domains that is submitted by normal users are inactive and user must has liz credit.
 * TODO: if user has not liz credit 403 error code(handle by event: PRE_WRITE), if he has credit we must add transaction to wallet and decrease his credit.
 *
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"domain:read", "user:read", "category:read"}},
 *          "denormalization_context"={"groups"={"domain:write"}}
 *     },
 *     itemOperations={
 *          "get"={
 *              "path"="domains/{id<\d+>}",
 *          },
 *          "findByDomain"={
 *              "method"="get",
 *              "path"="domains/{id}",
 *          },
 *          "put"={
 *              "normalization_context"={"groups"={"domain:read", "domain:read:by-owner"}},
 *              "denormalization_context"={"groups"={"domain:write", "domain:update:by-owner"}},
 *              "access_control"="is_granted('ROLE_ADMIN') or previous_object.getOwner() === user",
 *              "access_control_message"="You can not update others domains.",
 *          },
 *     },
 *     collectionOperations={
 *          "post",
 *          "get"={
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          },
 *     },
 * )
 *
 * @ApiFilter(DateFilter::class, properties={"registrationDate"})
 * @ApiFilter(SearchFilter::class, properties={"name": "ipartial", "domain": "ipartial"})
 * @ApiFilter(NumericFilter::class, properties={"status", "lastReportStatus"})
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"id", "name", "domain", "registrationDate", "status", "lastReportStatus", "lastReportAt"}
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\DomainRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="IDX_STATUS", columns={"status"}),
 *          @ORM\Index(name="IDX_DOMAIN_STATUS", columns={"domain", "status"}),
 *          @ORM\Index(name="IDX_LAST_REPORT", columns={"last_report_status", "last_report_at"}),
 *          @ORM\Index(name="IDX_LAST_REPORT_STATUS", columns={"last_report_status", "last_report_at", "status"}),
 *          @ORM\Index(name="IDX_LAST_AUDIT", columns={"last_audit_status", "last_audit_at"}),
 *          @ORM\Index(name="IDX_SCORE", columns={"score", "score_updated_at"}),
 *          @ORM\Index(name="IDX_LAST_REPORT_QUALITY", columns={"last_report_quality"}),
 *          @ORM\Index(name="IDX_ORDER_BY", columns={"last_report_status", "last_report_quality", "score"}),
 *      }
 * )
 *
 * @UniqueEntity(fields={"domain"})
 */
class Domain
{
    const STATUS_UNDEFINED = -1;
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    const REPORT_NOT_FOUND = -2;
    const REPORT_FAILED = -1;
    const REPORT_IN_PROGRESS = 1;
    const REPORT_FINISHED = 2;

    /**
     * @var int The db identifier
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"domain:read"})
     */
    private $id;

    /**
     * @var string the site name
     *
     * @ORM\Column(nullable=true)
     *
     * @Groups({"domain:read", "domain:write:by-admin", "domain:update:by-admin", "domain:update:by-owner"})
     *
     * @Assert\Length(max="255")
     */
    private $name;

    /**
     * @var string the domain of site
     *
     * @ORM\Column(length=100, unique=true)
     *
     * @Groups({"domain:read", "domain:write"})
     *
     * @Assert\NotNull()
     * @Assert\Length(max="100")
     */
    private $domain;

    /**
     * @var \DateTime the registration date of domain in ours db
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     *
     * @Groups({"domain:read"})
     */
    private $registrationDate;

    /**
     * @var User the user that add domain to ours db
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @Gedmo\Blameable(on="create")
     *
     * @Assert\Type("App\Entity\User")
     *
     * @Groups({"domain:read:by-admin"})
     */
    private $registerBy;

    /**
     * @var int the domain status, is it active or not?
     *
     * @ORM\Column(type="smallint")
     *
     * @Groups({"domain:read", "domain:write:by-admin", "domain:update:by-admin"})
     *
     * @Assert\NotNull()
     */
    private $status;

    /**
     * @var User the user that is owner of domain
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="domains")
     *
     * @Groups({"domain:read:by-admin", "domain:write:by-admin", "domain:update:by-admin"})
     *
     * @Assert\Type("App\Entity\User")
     */
    private $owner;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Groups({"domain:read:by-admin"})
     */
    private $lastReportStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Groups({"domain:read:by-admin"})
     */
    private $lastReportAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Category", inversedBy="domains")
     *
     * @Groups({"domain:read", "domain:write:by-admin", "domain:update:by-admin", "domain:update:by-owner"})
     */
    private $category;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"domain:read", "domain:write:by-admin", "domain:update:by-admin"})
     */
    private $secure;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Province")
     *
     * @Groups({"domain:read", "domain:write:by-admin", "domain:update:by-admin"})
     */
    private $province;

    /**
     * @ORM\Column(type="json")
     *
     * @Groups({"domain:read", "domain:write:by-admin", "domain:update:by-admin"})
     */
    private $details = [];

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DomainAudit", mappedBy="domain")
     */
    private $domainAudits;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"domain:read", "domain:write:by-admin", "domain:update:by-admin"})
     */
    private $screenshot;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Groups({"domain:read:by-admin"})
     */
    private $lastAuditStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Groups({"domain:read:by-admin"})
     */
    private $lastAuditAt;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Domain")
     * @ORM\JoinTable(
     *     name="domain_relations",
     *     joinColumns={@ORM\JoinColumn(name="domain_source", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="domain_target", referencedColumnName="id", onDelete="CASCADE")},
     * )
     */
    private $relatedDomains;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $scoreUpdatedAt;

    /**
     * @ORM\Column(type="decimal", nullable=true, precision=6, scale=3)
     */
    private $score;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $lastReportQuality;

    /**
     * Domain constructor.
     */
    public function __construct()
    {
        $this->setStatus(self::STATUS_INACTIVE);
        $this->domainAudits = new ArrayCollection();
        $this->relatedDomains = new ArrayCollection();
    }

    /**
     * @Groups({"domain:read"})
     */
    public function isVerified()
    {
        return (bool) $this->getOwner();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTimeInterface $registrationDate): self
    {
        $this->registrationDate = $registrationDate;

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

    public function getRegisterBy(): ?User
    {
        return $this->registerBy;
    }

    public function setRegisterBy(?User $registerBy): self
    {
        $this->registerBy = $registerBy;

        return $this;
    }

    public function getLastReportStatus(): ?int
    {
        return $this->lastReportStatus;
    }

    public function setLastReportStatus(?int $lastReportStatus): self
    {
        $this->lastReportStatus = $lastReportStatus;
        $this->setLastReportAt(new \DateTime());

        return $this;
    }

    public function getLastReportAt(): ?\DateTimeInterface
    {
        return $this->lastReportAt;
    }

    private function setLastReportAt(?\DateTimeInterface $lastReportAt): self
    {
        $this->lastReportAt = $lastReportAt;

        return $this;
    }

    public function __toString()
    {
        return sprintf('%s(%d)', $this->getDomain(), $this->getId());
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getSecure(): ?bool
    {
        return $this->secure;
    }

    public function isSecure(): ?bool
    {
        return $this->secure;
    }

    public function setSecure(?bool $secure): self
    {
        $this->secure = $secure;

        return $this;
    }

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(?Province $province): self
    {
        $this->province = $province;

        return $this;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(array $details): self
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @return Collection|DomainAudit[]
     */
    public function getDomainAudits(): Collection
    {
        return $this->domainAudits;
    }

    public function addDomainAudit(DomainAudit $domainAudit): self
    {
        if (!$this->domainAudits->contains($domainAudit)) {
            $this->domainAudits[] = $domainAudit;
            $domainAudit->setDomain($this);
        }

        return $this;
    }

    public function removeDomainAudit(DomainAudit $domainAudit): self
    {
        if ($this->domainAudits->contains($domainAudit)) {
            $this->domainAudits->removeElement($domainAudit);
            // set the owning side to null (unless already changed)
            if ($domainAudit->getDomain() === $this) {
                $domainAudit->setDomain(null);
            }
        }

        return $this;
    }

    public function getScreenshot(): ?string
    {
        return $this->screenshot;
    }

    public function setScreenshot(?string $screenshot): self
    {
        $this->screenshot = $screenshot;

        return $this;
    }

    public function getLastAuditStatus(): ?int
    {
        return $this->lastAuditStatus;
    }

    public function setLastAuditStatus(?int $lastAuditStatus): self
    {
        $this->lastAuditStatus = $lastAuditStatus;

        return $this;
    }

    public function getLastAuditAt(): ?\DateTimeInterface
    {
        return $this->lastAuditAt;
    }

    public function setLastAuditAt(?\DateTimeInterface $lastAuditAt): self
    {
        $this->lastAuditAt = $lastAuditAt;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getRelatedDomains(): Collection
    {
        return $this->relatedDomains;
    }

    public function addRelatedDomain(self $relatedDomain): self
    {
        if (!$this->relatedDomains->contains($relatedDomain)) {
            $this->relatedDomains[] = $relatedDomain;
        }

        return $this;
    }

    public function removeRelatedDomain(self $relatedDomain): self
    {
        if ($this->relatedDomains->contains($relatedDomain)) {
            $this->relatedDomains->removeElement($relatedDomain);
        }

        return $this;
    }

    public function getScoreUpdatedAt(): ?\DateTimeInterface
    {
        return $this->scoreUpdatedAt;
    }

    public function setScoreUpdatedAt(?\DateTimeInterface $scoreUpdatedAt): self
    {
        $this->scoreUpdatedAt = $scoreUpdatedAt;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getLastReportQuality(): ?int
    {
        return $this->lastReportQuality;
    }

    public function setLastReportQuality(?int $lastReportQuality): self
    {
        $this->lastReportQuality = $lastReportQuality;

        return $this;
    }
}
