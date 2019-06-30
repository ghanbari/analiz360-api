<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"downstream:read"}},
 *          "denormalization_context"={"groups"={"downstream:write"}}
 *     },
 *     itemOperations={
 *          "get"={
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          }
 *     },
 *     collectionOperations={}
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\DownstreamRepository")
 */
class Downstream
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
     * @ORM\ManyToOne(targetEntity="Report", inversedBy="downstreams")
     * @ORM\JoinColumn(name="report_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotNull()
     * @Assert\Type(type="\App\Entity\Report")
     *
     * @Groups({"downstream:read"})
     */
    private $report;

    /**
     * @var string
     *
     * @ORM\Column(length=100)
     *
     * @Assert\Length(max="100")
     * @Assert\NotNull()
     *
     * @Groups({"downstream:read"})
     */
    private $domain;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"downstream:read"})
     */
    private $percent;

    public static function create(array $data): self
    {
        $downstream = new static();
        $downstream->setReport($data['report']);
        $downstream->setPercent($data['percent']);
        $downstream->setDomain($data['domain']);

        return $downstream;
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
            $report->addDownstream($this);
        }

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
