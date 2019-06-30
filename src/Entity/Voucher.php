<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass="App\Repository\VoucherRepository")
 */
class Voucher
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $code;

    /**
     * @ORM\Column(type="smallint")
     */
    private $percent;

    /**
     * @ORM\Column(type="integer")
     */
    private $maxUsage;

    /**
     * @ORM\Column(type="smallint")
     */
    private $maxUsagePerUser;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $usableFrom;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $usableTill;

    /**
     * @var Product The voucher is usable for this product
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Product")
     */
    private $product;

    /**
     * @var int The voucher is usable for products that has this type
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $productType;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPercent(): ?int
    {
        return $this->percent;
    }

    public function setPercent(int $percent): self
    {
        $this->percent = $percent;

        return $this;
    }

    public function getMaxUsage(): ?int
    {
        return $this->maxUsage;
    }

    public function setMaxUsage(int $maxUsage): self
    {
        $this->maxUsage = $maxUsage;

        return $this;
    }

    public function getMaxUsagePerUser(): ?int
    {
        return $this->maxUsagePerUser;
    }

    public function setMaxUsagePerUser(int $maxUsagePerUser): self
    {
        $this->maxUsagePerUser = $maxUsagePerUser;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getUsableFrom(): ?\DateTimeInterface
    {
        return $this->usableFrom;
    }

    public function setUsableFrom(?\DateTimeInterface $usableFrom): self
    {
        $this->usableFrom = $usableFrom;

        return $this;
    }

    public function getUsableTill(): ?\DateTimeInterface
    {
        return $this->usableTill;
    }

    public function setUsableTill(?\DateTimeInterface $usableTill): self
    {
        $this->usableTill = $usableTill;

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

    public function getProductType(): ?int
    {
        return $this->productType;
    }

    public function setProductType(?int $productType): self
    {
        $this->productType = $productType;

        return $this;
    }
}
