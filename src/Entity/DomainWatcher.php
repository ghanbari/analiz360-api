<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Annotation\OwnerAware;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"domain_watcher:read", "domain:read"}},
 *          "denormalization_context"={"groups"={"domain_watcher:write"}},
 *     },
 *     itemOperations={
 *          "get"
 *     },
 *     collectionOperations={
 *          "get",
 *     }
 * )
 *
 * @ApiFilter(DateFilter::class, properties={"expireAt"})
 * @ApiFilter(SearchFilter::class, properties={"domain.domain": "iexact"})
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"domain.domain", "expireAt": "DESC"}
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\DomainWatcherRepository")
 * @OwnerAware(userFieldName="watcher")
 */
class DomainWatcher
{
    use TimestampableEntity;
    use BlameableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Domain")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     *
     * @Groups({"domain_watcher:read"})
     */
    private $domain;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="domainWatchers")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups({"domain_watcher:read"})
     */
    private $watcher;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Groups({"domain_watcher:read"})
     */
    private $expireAt;

    /**
     * @ORM\Column(type="smallint")
     *
     * @Groups({"domain_watcher:read"})
     */
    private $history;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Product")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups({"domain_watcher:read"})
     */
    private $product;

    /**
     * DomainWatcher constructor.
     *
     * @param Domain  $domain
     * @param User    $watcher
     * @param Product $product
     *
     * @throws \Exception
     */
    public function __construct(Domain $domain, User $watcher, Product $product)
    {
        $this->domain = $domain;
        $this->watcher = $watcher;
        $this->product = $product;
        $this->expireAt = new \DateTime(sprintf('+%d days', $product->getService()['duration']));
        $this->history = $product->getService()['history'];
    }

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

    public function getWatcher(): ?User
    {
        return $this->watcher;
    }

    public function setWatcher(?User $watcher): self
    {
        $this->watcher = $watcher;

        return $this;
    }

    public function getExpireAt(): ?\DateTimeInterface
    {
        return $this->expireAt;
    }

    public function setExpireAt(\DateTimeInterface $expireAt): self
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    public function getHistory(): ?int
    {
        return $this->history;
    }

    public function setHistory(int $history): self
    {
        $this->history = $history;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }
}
