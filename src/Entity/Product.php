<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use App\Annotation\TimeAware;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"product:read", "media:full-path", "resource:time"}},
 *          "denormalization_context"={"groups"={"product:write"}}
 *     },
 *     itemOperations={
 *          "get",
 *          "put"={
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          },
 *          "delete"={
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          },
 *     },
 *     collectionOperations={
 *          "post"={
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          },
 *          "get",
 *     },
 * )
 *
 * @ApiFilter(DateFilter::class, properties={"visibleFrom", "visibleTill"})
 * @ApiFilter(SearchFilter::class, properties={"title": "ipartial"})
 * @ApiFilter(NumericFilter::class, properties={"unit", "type", "active"})
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"id", "price", "unit", "type", "title", "visibleFrom", "visibleTill", "active"}
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 * @TimeAware(visibleStrictlyBefore="visibleFrom", visibleStrictlyAfter="visibleTill")
 */
class Product
{
    use TimestampableEntity;
    use BlameableEntity;

    const TYPE_LIZ_PACK = 1;
    const TYPE_ALEXA_ADD_DOMAIN = 2;
    const TYPE_ALEXA_WATCH_DOMAIN = 3;
    const TYPE_ALEXA_INCREASE_FREE_WATCH_SIZE = 4;
    const TYPES = [
        self::TYPE_LIZ_PACK,
        self::TYPE_ALEXA_ADD_DOMAIN,
        self::TYPE_ALEXA_WATCH_DOMAIN,
        self::TYPE_ALEXA_INCREASE_FREE_WATCH_SIZE,
    ];

    /**
     * @var int the db identifier
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"product:read"})
     */
    private $id;

    /**
     * @var int the product price
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotNull()
     * @Assert\Type("numeric")
     *
     * @Groups({"product:read", "product:write:by-admin", "product:update:by-admin"})
     */
    private $price;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\NotNull()
     * @Assert\Choice(choices={Wallet::UNIT_RIALS, Wallet::UNIT_LIZ})
     *
     * @Groups({"product:read", "product:write:by-admin", "product:update:by-admin"})
     */
    private $unit;

    /**
     * @var int The product's service type
     *
     * @ORM\Column(type="smallint")
     *
     * @ApiProperty(
     *     attributes={
     *         "swagger_context"={
     *             "type"="string",
     *             "enum"=Product::TYPES,
     *         }
     *     }
     * )
     *
     * @Assert\NotNull()
     * @Assert\Choice(choices=Product::TYPES)
     *
     * @Groups({"product:read", "product:write:by-admin", "product:update:by-admin"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotNull()
     * @Assert\Length(max="255")
     *
     * @Groups({"product:read", "product:write:by-admin", "product:update:by-admin"})
     */
    private $title;

    /**
     * @var Media
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Media")
     *
     * @Groups({"product:read", "product:write:by-admin", "product:update:by-admin"})
     */
    private $image;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\DateTime()
     *
     * @Groups({"product:read", "product:write:by-admin", "product:update:by-admin"})
     */
    private $visibleFrom;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\DateTime()
     *
     * @Groups({"product:read", "product:write:by-admin", "product:update:by-admin"})
     */
    private $visibleTill;

    /**
     * @ORM\Column(type="json")
     *
     * @Assert\NotNull()
     *
     * @Groups({"product:read", "product:write:by-admin", "product:update:by-admin"})
     */
    private $service = [];

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type("bool")
     *
     * @Groups({"product:read:by-admin", "product:write:by-admin", "product:update:by-admin"})
     */
    private $active;

    /**
     * Product constructor.
     *
     * @param int    $price
     * @param int    $type
     * @param string $title
     * @param array  $service
     */
    public function __construct(int $price, int $type, string $title, array $service)
    {
        $this->setPrice($price);
        $this->setType($type);
        $this->setTitle($title);
        $this->setService($service);
        $this->setActive(true);
    }

    /**
     * @Groups({"media:full-path"})
     *
     * @return string
     */
    public function getImagePath()
    {
        return 'media/cache/resolve/product_large/media/'.(!is_null($this->image) ? $this->image->getContentUrl() : 'default_product.jpg');
    }

    /**
     * @Assert\Callback()
     *
     * @param ExecutionContextInterface $context
     */
    public function isServiceValid(ExecutionContextInterface $context)
    {
        switch ($this->getType()) {
            case self::TYPE_LIZ_PACK:
                if (!array_key_exists('lizAmount', $this->getService())) {
                    $context->buildViolation('You should define liz amount')
                        ->atPath('service')
                        ->addViolation();
                }
                break;
            case self::TYPE_ALEXA_ADD_DOMAIN:
            case self::TYPE_ALEXA_WATCH_DOMAIN:
                if (!array_key_exists('duration', $this->getService()) or !array_key_exists('history', $this->getService())) {
                    $context->buildViolation('you must specify "history" & "duration"')
                        ->atPath('service')
                        ->addViolation();
                }
                break;
            case self::TYPE_ALEXA_INCREASE_FREE_WATCH_SIZE:
                if (!array_key_exists('duration', $this->getService())) {
                    $context->buildViolation('you must specify "duration"')
                        ->atPath('service')
                        ->addViolation();
                }
                break;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getUnit(): ?int
    {
        return $this->unit;
    }

    private function setUnit(int $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        switch ($type) {
            case self::TYPE_LIZ_PACK:
                $this->setUnit(Wallet::UNIT_RIALS);
                break;
            case self::TYPE_ALEXA_ADD_DOMAIN:
                $this->setUnit(Wallet::UNIT_LIZ);
                break;
            case self::TYPE_ALEXA_WATCH_DOMAIN:
                $this->setUnit(Wallet::UNIT_LIZ);
                break;
            case self::TYPE_ALEXA_INCREASE_FREE_WATCH_SIZE:
                $this->setUnit(Wallet::UNIT_LIZ);
                break;
        }

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

    public function getImage(): ?Media
    {
        return $this->image;
    }

    public function setImage(?Media $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getVisibleFrom(): ?\DateTimeInterface
    {
        return $this->visibleFrom;
    }

    public function setVisibleFrom(?\DateTimeInterface $visibleFrom): self
    {
        $this->visibleFrom = $visibleFrom;

        return $this;
    }

    public function getVisibleTill(): ?\DateTimeInterface
    {
        return $this->visibleTill;
    }

    public function setVisibleTill(?\DateTimeInterface $visibleTill): self
    {
        $this->visibleTill = $visibleTill;

        return $this;
    }

    public function getService(): ?array
    {
        return $this->service;
    }

    public function setService(array $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
