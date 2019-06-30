<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Annotation\OwnerAware;
use App\Exception\Wallet\LowCreditException;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"wallet:read", "user:read"}},
 *          "denormalization_context"={"groups"={"wallet:write"}}
 *     },
 *     itemOperations={
 *          "get"={
 *              "access_control"="object.getOwner() === user or is_granted('ROLE_ADMIN')",
 *              "access_control_message"="this wallet is not yours.",
 *          }
 *     },
 *     collectionOperations={
 *          "get",
 *     },
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"owner": "exact"})
 * @ApiFilter(OrderFilter::class,properties={"id", "amount", "unit", "type", "status", "createdAt"})
 *
 * @ORM\Entity(repositoryClass="App\Repository\WalletRepository")
 * @ORM\HasLifecycleCallbacks()
 * @OwnerAware(userFieldName="owner")
 */
class Wallet
{
    use TimestampableEntity;
    use BlameableEntity;

    const TYPE_INCOME = 1;

    const TYPE_OUTCOME = -1;

    const STATUS_FAILED = -1;

    const STATUS_SUCCESS = 1;

    const UNIT_RIALS = 1;

    const UNIT_LIZ = 2;

    /**
     * @var int the db identifier
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"wallet:read"})
     */
    private $id;

    /**
     * @var User the owner of wallet
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups({"wallet:read", "wallet:write"})
     */
    private $owner;

    /**
     * @var int the transaction's amount
     *
     * @ORM\Column(type="integer")
     *
     * @Groups({"wallet:read"})
     */
    private $amount;

    /**
     * @var int specify unit of transaction, can be Rial(1) or Liz(2)
     *
     * @ORM\Column(type="smallint")
     *
     * @Groups({"wallet:read", "wallet:write:auto"})
     */
    private $unit;

    /**
     * @var Voucher
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Voucher")
     *
     * @Groups({"wallet:read", "wallet:write"})
     */
    private $voucher;

    /**
     * @var int the transaction type(Income or Outcome)
     *
     * @ORM\Column(type="smallint")
     *
     * @Groups({"wallet:read", "wallet:write:auto"})
     */
    private $type;

    /**
     * @ORM\Column(type="json")
     *
     * @Groups({"wallet:read", "wallet:write:auto"})
     */
    private $info = [];

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     *
     * @Groups({"wallet:read", "wallet:write:auto"})
     */
    private $description;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     *
     * @Groups({"wallet:read", "wallet:write:auto"})
     */
    protected $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Order")
     * @ORM\JoinColumn()
     */
    private $order;

    /**
     * Wallet constructor.
     *
     * @param User $owner
     * @param int  $amount
     * @param int  $type
     * @param $description
     * @param int $unit
     */
    public function __construct(User $owner, int $amount, int $type, $description, int $unit = self::UNIT_LIZ)
    {
        $this->setOwner($owner);
        $this->setAmount($amount);
        $this->setUnit($unit);
        $this->setType($type);
        $this->setDescription($description);
    }

    public static function createFromOrder(Order $order)
    {
        $wallet = new static(
            $order->getUser(),
            $order->getProduct()->getPrice(),
            self::TYPE_OUTCOME,
            $order->getProduct()->getTitle(),
            $order->getProduct()->getUnit()
        );

        $wallet->setVoucher($order->getVoucher());
        $wallet->setOrder($order);

        return $wallet;
    }

    /**
     * @ORM\PrePersist()
     */
    public function updateUserCredit()
    {
        if (self::UNIT_LIZ === $this->getUnit() && !in_array('ROLE_ADMIN', $this->getOwner()->getRoles())) {
            if (self::TYPE_OUTCOME === $this->getType() && $this->getOwner()->getCredit() < $this->getAmount()) {
                throw new LowCreditException();
            }

            $this->getOwner()->setCredit($this->getOwner()->getCredit() + ($this->getAmount() * $this->getType()));
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUnit(): ?int
    {
        return $this->unit;
    }

    public function setUnit(int $unit): self
    {
        $this->unit = $unit;

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

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): self
    {
        $this->order = $order;

        return $this;
    }

//    public function setBox(?Box $box): self
//    {
//        $this->box = $box;
//        $this->setAmount($box->getPrice());
//        $this->setType(self::TYPE_INCOME);
//        $this->setUnit(self::UNIT_RIALS);
//
//        return $this;
//    }
}
