<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"backLink:read"}},
 *          "denormalization_context"={"groups"={"backLink:write"}}
 *     },
 *     itemOperations={
 *          "get"={
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          }
 *     },
 *     collectionOperations={}
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\BacklinkRepository")
 */
class Backlink
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
     * @ORM\ManyToOne(targetEntity="Report", inversedBy="backlinks")
     * @ORM\JoinColumn(name="report_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotNull()
     * @Assert\Type(type="\App\Entity\Report")
     *
     * @Groups({"backLink:read"})
     */
    private $report;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Range(max="100")
     * @Assert\NotNull()
     *
     * @Groups({"backLink:read"})
     */
    private $rank;

    /**
     * @var string
     *
     * @ORM\Column(length=100)
     *
     * @Assert\NotNull()
     * @Assert\Length(max="100")
     *
     * @Groups({"backLink:read"})
     */
    private $domain;

    /**
     * @var string
     *
     * @ORM\Column(length=2000)
     *
     * @Assert\Length(max="2000")
     * @Assert\NotNull()
     *
     * @Groups({"backLink:read"})
     */
    private $page;

    public static function create(array $data): self
    {
        $backlink = new static();
        $backlink->setReport($data['report']);
        $backlink->setDomain($data['domain']);
        $backlink->setRank($data['rank']);
        $backlink->setPage($data['page']);

        return $backlink;
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
            $report->addBacklink($this);
        }

        return $this;
    }

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function setRank(int $rank): self
    {
        $this->rank = $rank;

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

    public function getPage(): ?string
    {
        return $this->page;
    }

    public function setPage(string $page): self
    {
        $this->page = $page;

        return $this;
    }
}
