<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"topPage:read"}},
 *          "denormalization_context"={"groups"={"topPage:write"}}
 *     },
 *     itemOperations={
 *          "get"={
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          }
 *     },
 *     collectionOperations={}
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\ToppageRepository")
 */
class Toppage
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Report
     *
     * @ORM\ManyToOne(targetEntity="Report", inversedBy="toppages")
     * @ORM\JoinColumn(name="report_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotNull()
     * @Assert\Type(type="\App\Entity\Report")
     *
     * @Groups({"topPage:read"})
     */
    private $report;

    /**
     * @var string
     *
     * @ORM\Column(length=500)
     *
     * @Assert\NotNull()
     * @Assert\Length(max="500")
     *
     * @Groups({"topPage:read"})
     */
    private $address;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"topPage:read"})
     */
    private $percent;

    public static function create(array $data): self
    {
        $toppage = new static();
        $toppage->setReport($data['report']);
        $toppage->setAddress($data['address']);
        $toppage->setPercent($data['percent']);

        return $toppage;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReport(): ?Report
    {
        return $this->report;
    }

    public function setReport(?Report $report): self
    {
        $this->report = $report;
        if (null !== $report) {
            $report->addToppage($this);
        }

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getPercent(): ?float
    {
        return $this->percent;
    }

    public function setPercent(?float $percent): self
    {
        $this->percent = $percent;

        return $this;
    }
}
