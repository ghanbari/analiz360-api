<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"order:read", "user:read", "product:read", "media:full-path", "resource:time"}},
 *          "denormalization_context"={"groups"={"order:write"}},
 *     },
 *     itemOperations={
 *          "get"={"access_control"="object.getUser() === user"},
 *     },
 *     collectionOperations={
 *          "post"={},
 *          "get"={"access_control"="false", "deprecation_reason"="Only for client generator"},
 *     },
 * )
 * @ORM\Entity(repositoryClass="App\Repository\OrderRepository")
 * @ORM\Table(name="`order`")
 */
class Order
{
    use TimestampableEntity;
    use BlameableEntity;

    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 0;
    const STATUS_CANCEL = -1;

    /**
     * @var int The database identifier
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"order:read"})
     */
    private $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="orders")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups({"order:read", "order:write:by-admin"})
     *
     * @Assert\NotNull(groups={"Order:auto"})
     * @Assert\Type("App\Entity\User")
     * @Gedmo\Blameable(on="create")
     */
    private $user;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Product")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups({"order:read", "order:write"})
     *
     * @Assert\NotNull()
     * @Assert\Type("App\Entity\Product")
     */
    private $product;

    /**
     * @ORM\Column(type="json")
     *
     * @Groups({"order:read", "order:write"})
     */
    private $info = [];

    /**
     * TODO: check voucher is only for a specific product or all products.
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Voucher")
     *
     * @Groups({"order:read", "order:write"})
     *
     * @Assert\Type("App\Entity\Voucher")
     */
    private $voucher;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Groups({"order:read", "order:write:auto"})
     */
    private $status;

    /**
     * Order constructor.
     *
     * @param $user
     * @param $product
     * @param array $info
     */
    public function __construct(User $user = null, Product $product = null, array $info = [])
    {
        $this->user = $user;
        $this->product = $product;
        $this->info = $info;
    }

    /**
     * @Assert\Callback()
     *
     * @param ExecutionContextInterface $context
     */
    public function isServiceValid(ExecutionContextInterface $context)
    {
        switch ($this->getProduct()->getType()) {
            case Product::TYPE_LIZ_PACK:
                break;
            case Product::TYPE_ALEXA_ADD_DOMAIN:
                $context->buildViolation('This product type is not supported, you must send post request to "/api/domains"')
                    ->atPath('product')
                    ->addViolation();
                break;
            case Product::TYPE_ALEXA_WATCH_DOMAIN:
                if (!array_key_exists('domain', $this->info)) {
                    $context->buildViolation('you must specify "domain" for this order. info["domain"] = "google.com"')
                        ->atPath('info')
                        ->addViolation();
                }
                break;
            case Product::TYPE_ALEXA_INCREASE_FREE_WATCH_SIZE:
                break;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getInfo(): ?array
    {
        return $this->info;
    }

    public function setInfo(array $info): self
    {
        $this->info = $info;

        return $this;
    }

    public function getVoucher(): ?Voucher
    {
        return $this->voucher;
    }

    public function setVoucher(?Voucher $voucher): self
    {
        $this->voucher = $voucher;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
