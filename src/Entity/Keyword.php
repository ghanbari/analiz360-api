<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"keyword:read"}},
 *          "denormalization_context"={"groups"={"keyword:write"}}
 *     },
 *     itemOperations={
 *          "get"={
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          }
 *     },
 *     collectionOperations={}
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\KeywordRepository")
 */
class Keyword
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
     * @ORM\ManyToOne(targetEntity="Report", inversedBy="keywords")
     * @ORM\JoinColumn(name="report_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotNull()
     * @Assert\Type(type="\App\Entity\Report")
     *
     * @Groups({"keyword:read"})
     */
    private $report;

    /**
     * @var string
     *
     * @ORM\Column()
     *
     * @Assert\Length(max="255")
     * @Assert\NotNull()
     *
     * @Groups({"keyword:read"})
     */
    private $keyword;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"keyword:read"})
     */
    private $percent;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"keyword:read"})
     */
    private $sharePercent;

    public static function create(array $data): self
    {
        $keyword = new static();
        $keyword->setReport($data['report']);
        $keyword->setPercent($data['percent']);
        $keyword->setSharePercent($data['sharePercent']);
        $keyword->setKeyword($data['keyword']);

        return $keyword;
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
            $report->addKeyword($this);
        }

        return $this;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): self
    {
        $this->keyword = $keyword;

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

    public function getSharePercent(): ?float
    {
        return $this->sharePercent;
    }

    public function setSharePercent(?float $sharePercent): self
    {
        $this->sharePercent = $sharePercent;

        return $this;
    }
}
