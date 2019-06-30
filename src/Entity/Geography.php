<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"geo:read"}},
 *          "denormalization_context"={"groups"={"geo:write"}}
 *     },
 *     itemOperations={
 *          "get"={
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          }
 *     },
 *     collectionOperations={}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\GeographyRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="IDX_REPORT_COUNTRY", columns={"report_id", "country_id"}),
 *      }
 * )
 */
class Geography
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
     * @ORM\ManyToOne(targetEntity="Report", inversedBy="geographies")
     * @ORM\JoinColumn(name="report_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotNull()
     * @Assert\Type(type="\App\Entity\Report")
     *
     * @Groups({"geo:read"})
     */
    private $report;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id", nullable=false)
     *
     * @Assert\NotNull()
     * @Assert\Type(type="\App\Entity\Country")
     *
     * @Groups({"geo:read"})
     */
    private $country;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"geo:read"})
     */
    private $visitorsPercent;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"geo:read"})
     */
    private $rank;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"geo:read"})
     */
    private $pageViewsPerUser;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"geo:read"})
     */
    private $pageViewsPercent;

    public static function create($data): self
    {
        $geography = new static();
        $geography->setCountry($data['country']);
        $geography->setVisitorsPercent($data['visitorsPercent']);
        $geography->setPageViewsPercent($data['pageViewsPercent']);
        $geography->setPageViewsPerUser($data['pageViewsPerUser']);
        $geography->setReport($data['report']);
        $geography->setRank($data['rank']);

        return $geography;
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
            $report->addGeography($this);
        }

        return $this;
    }

    public function getVisitorsPercent(): ?float
    {
        return $this->visitorsPercent;
    }

    public function setVisitorsPercent(?float $visitorsPercent): self
    {
        $this->visitorsPercent = $visitorsPercent;

        return $this;
    }

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function setRank(?int $rank): self
    {
        $this->rank = $rank;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getPageViewsPerUser(): ?float
    {
        return $this->pageViewsPerUser;
    }

    public function setPageViewsPerUser(?float $pageViewsPerUser): self
    {
        $this->pageViewsPerUser = $pageViewsPerUser;

        return $this;
    }

    public function getPageViewsPercent(): ?float
    {
        return $this->pageViewsPercent;
    }

    public function setPageViewsPercent(?float $pageViewsPercent): self
    {
        $this->pageViewsPercent = $pageViewsPercent;

        return $this;
    }
}
