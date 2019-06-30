<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Controller\GetReportAction;

/**
 * @ApiResource(
 *     attributes={
 *          "normalization_context"={"groups"={"report:read", "geo:read", "keyword:read", "upstream:read", "downstream:read", "backLink:read", "topPage:read"}},
 *          "denormalization_context"={"groups"={"report:write"}},
 *     },
 *     itemOperations={
 *          "get"={
 *              "controller"=GetReportAction::class,
 *              "path"="domains/{id}/reports/{date}",
 *              "defaults"={"_api_receive"=false, "date"=""},
 *          },
 *     },
 *     collectionOperations={},
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\ReportRepository")
 * @ORM\Table(
 *      uniqueConstraints={@UniqueConstraint(name="IDX_DOMAIN_DATE", columns={"domain_id", "date"})},
 *      indexes={
 *          @Index(name="IDX_DOMAIN_DATE", columns={"domain_id", "date"}),
 *          @Index(name="IDX_DATE", columns={"date"}),
 *          @Index(name="IDX_GLOBAL_RANK", columns={"global_rank"}),
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Report
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"report:read"})
     *
     * @ApiProperty(identifier=false)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="Domain", cascade={"DETACH"})
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotNull()
     * @Assert\Type(type="App\Entity\Domain")
     *
     * @Groups({"report:read"})
     *
     * @ApiProperty(identifier=true)
     */
    private $domain;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date")
     *
     * @Assert\NotNull()
     * @Assert\Type(type="\DateTime")
     *
     * @Groups({"report:read"})
     */
    private $date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="time")
     *
     * @Assert\NotNull()
     * @Assert\Type(type="\DateTime")
     */
    private $time;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotNull()
     * @Assert\Type(type="numeric")
     *
     * @Groups({"report:read"})
     */
    private $globalRank;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"report:read"})
     */
    private $globalRankChange;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"report:read"})
     */
    private $engageRate;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"report:read"})
     */
    private $engageRateChange;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"report:read"})
     */
    private $dailyPageView;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"report:read"})
     */
    private $dailyPageViewChange;

    /**
     * @var int in second
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"report:read"})
     */
    private $dailyTimeOnSite;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"report:read"})
     */
    private $dailyTimeOnSiteChange;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"report:read"})
     */
    private $searchVisit;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"report:read"})
     */
    private $searchVisitChange;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="numeric")
     *
     * @Groups({"report:read"})
     */
    private $totalSiteLinking;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"report:read"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     *
     * @Assert\Length(max="255")
     *
     * @Groups({"report:read"})
     */
    private $loadSpeed;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Geography", mappedBy="report", cascade={"DETACH"})
     *
     * @Groups({"geo:read"})
     */
    private $geographies;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Keyword", mappedBy="report", cascade={"DETACH"})
     *
     * @Groups({"keyword:read"})
     */
    private $keywords;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Upstream", mappedBy="report", cascade={"DETACH"})
     *
     * @Groups({"upstream:read"})
     */
    private $upstreams;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Downstream", mappedBy="report", cascade={"DETACH"})
     *
     * @Groups({"downstream:read"})
     */
    private $downstreams;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Backlink", mappedBy="report", cascade={"DETACH"})
     *
     * @Groups({"backLink:read"})
     */
    private $backlinks;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Toppage", mappedBy="report", cascade={"DETACH"})
     *
     * @Groups({"topPage:read"})
     */
    private $toppages;

    /**
     * @var int show completed percent
     *
     * @ORM\Column(type="smallint")
     */
    private $status = 0;

    public function __construct()
    {
        $this->setDate(new \DateTime());
        $this->setTime(new \DateTime());
        $this->geographies = new ArrayCollection();
        $this->keywords = new ArrayCollection();
        $this->upstreams = new ArrayCollection();
        $this->downstreams = new ArrayCollection();
        $this->backlinks = new ArrayCollection();
        $this->toppages = new ArrayCollection();
    }

    public static function create(array $data): self
    {
        $report = new static();
        $report->domain = $data['domain'];
        $report->globalRank = $data['globalRank'];
        $report->globalRankChange = $data['globalRankChange'];
        $report->engageRate = $data['engageRate'];
        $report->engageRateChange = $data['engageRateChange'];
        $report->dailyPageView = $data['dailyPageView'];
        $report->dailyPageViewChange = $data['dailyPageViewChange'];
        $report->dailyTimeOnSite = $data['dailyTimeOnSite'];
        $report->dailyTimeOnSiteChange = $data['dailyTimeOnSiteChange'];
        $report->searchVisit = $data['searchVisit'];
        $report->searchVisitChange = $data['searchVisitChange'];
        $report->totalSiteLinking = $data['totalSiteLinking'];
        $report->description = $data['description'];
        $report->loadSpeed = $data['loadSpeed'];

        return $report;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getGlobalRank(): ?int
    {
        return $this->globalRank;
    }

    public function setGlobalRank(int $globalRank): self
    {
        $this->globalRank = $globalRank;

        return $this;
    }

    public function getGlobalRankChange(): ?int
    {
        return $this->globalRankChange;
    }

    public function setGlobalRankChange(?int $globalRankChange): self
    {
        $this->globalRankChange = $globalRankChange;

        return $this;
    }

    public function getEngageRate(): ?float
    {
        return $this->engageRate;
    }

    public function setEngageRate(?float $engageRate): self
    {
        $this->engageRate = $engageRate;

        return $this;
    }

    public function getEngageRateChange(): ?float
    {
        return $this->engageRateChange;
    }

    public function setEngageRateChange(?float $engageRateChange): self
    {
        $this->engageRateChange = $engageRateChange;

        return $this;
    }

    public function getDailyPageView(): ?float
    {
        return $this->dailyPageView;
    }

    public function setDailyPageView(?float $dailyPageView): self
    {
        $this->dailyPageView = $dailyPageView;

        return $this;
    }

    public function getDailyPageViewChange(): ?float
    {
        return $this->dailyPageViewChange;
    }

    public function setDailyPageViewChange(?float $dailyPageViewChange): self
    {
        $this->dailyPageViewChange = $dailyPageViewChange;

        return $this;
    }

    public function getDailyTimeOnSite(): ?int
    {
        return $this->dailyTimeOnSite;
    }

    public function setDailyTimeOnSite(?int $dailyTimeOnSite): self
    {
        $this->dailyTimeOnSite = $dailyTimeOnSite;

        return $this;
    }

    public function getDailyTimeOnSiteChange(): ?float
    {
        return $this->dailyTimeOnSiteChange;
    }

    public function setDailyTimeOnSiteChange(?float $dailyTimeOnSiteChange): self
    {
        $this->dailyTimeOnSiteChange = $dailyTimeOnSiteChange;

        return $this;
    }

    public function getSearchVisit(): ?float
    {
        return $this->searchVisit;
    }

    public function setSearchVisit(?float $searchVisit): self
    {
        $this->searchVisit = $searchVisit;

        return $this;
    }

    public function getSearchVisitChange(): ?float
    {
        return $this->searchVisitChange;
    }

    public function setSearchVisitChange(?float $searchVisitChange): self
    {
        $this->searchVisitChange = $searchVisitChange;

        return $this;
    }

    public function getTotalSiteLinking(): ?int
    {
        return $this->totalSiteLinking;
    }

    public function setTotalSiteLinking(?int $totalSiteLinking): self
    {
        $this->totalSiteLinking = $totalSiteLinking;

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

    public function getLoadSpeed(): ?string
    {
        return $this->loadSpeed;
    }

    public function setLoadSpeed(?string $loadSpeed): self
    {
        $this->loadSpeed = $loadSpeed;

        return $this;
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

    /**
     * @return Collection|Geography[]
     */
    public function getGeographies(): Collection
    {
        return $this->geographies;
    }

    public function addGeography(Geography $geography): self
    {
        if (!$this->geographies->contains($geography)) {
            $this->geographies[] = $geography;
            $geography->setReport($this);
        }

        return $this;
    }

    public function removeGeography(Geography $geography): self
    {
        if ($this->geographies->contains($geography)) {
            $this->geographies->removeElement($geography);
            // set the owning side to null (unless already changed)
            if ($geography->getReport() === $this) {
                $geography->setReport(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Keyword[]
     */
    public function getKeywords(): Collection
    {
        return $this->keywords;
    }

    public function addKeyword(Keyword $keyword): self
    {
        if (!$this->keywords->contains($keyword)) {
            $this->keywords[] = $keyword;
            $keyword->setReport($this);
        }

        return $this;
    }

    public function removeKeyword(Keyword $keyword): self
    {
        if ($this->keywords->contains($keyword)) {
            $this->keywords->removeElement($keyword);
            // set the owning side to null (unless already changed)
            if ($keyword->getReport() === $this) {
                $keyword->setReport(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Upstream[]
     */
    public function getUpstreams(): Collection
    {
        return $this->upstreams;
    }

    public function addUpstream(Upstream $upstream): self
    {
        if (!$this->upstreams->contains($upstream)) {
            $this->upstreams[] = $upstream;
            $upstream->setReport($this);
        }

        return $this;
    }

    public function removeUpstream(Upstream $upstream): self
    {
        if ($this->upstreams->contains($upstream)) {
            $this->upstreams->removeElement($upstream);
            // set the owning side to null (unless already changed)
            if ($upstream->getReport() === $this) {
                $upstream->setReport(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Downstream[]
     */
    public function getDownstreams(): Collection
    {
        return $this->downstreams;
    }

    public function addDownstream(Downstream $downstream): self
    {
        if (!$this->downstreams->contains($downstream)) {
            $this->downstreams[] = $downstream;
            $downstream->setReport($this);
        }

        return $this;
    }

    public function removeDownstream(Downstream $downstream): self
    {
        if ($this->downstreams->contains($downstream)) {
            $this->downstreams->removeElement($downstream);
            // set the owning side to null (unless already changed)
            if ($downstream->getReport() === $this) {
                $downstream->setReport(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Backlink[]
     */
    public function getBacklinks(): Collection
    {
        return $this->backlinks;
    }

    public function addBacklink(Backlink $backlink): self
    {
        if (!$this->backlinks->contains($backlink)) {
            $this->backlinks[] = $backlink;
            $backlink->setReport($this);
        }

        return $this;
    }

    public function removeBacklink(Backlink $backlink): self
    {
        if ($this->backlinks->contains($backlink)) {
            $this->backlinks->removeElement($backlink);
            // set the owning side to null (unless already changed)
            if ($backlink->getReport() === $this) {
                $backlink->setReport(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Toppage[]
     */
    public function getToppages(): Collection
    {
        return $this->toppages;
    }

    public function addToppage(Toppage $toppage): self
    {
        if (!$this->toppages->contains($toppage)) {
            $this->toppages[] = $toppage;
            $toppage->setReport($this);
        }

        return $this;
    }

    public function removeToppage(Toppage $toppage): self
    {
        if ($this->toppages->contains($toppage)) {
            $this->toppages->removeElement($toppage);
            // set the owning side to null (unless already changed)
            if ($toppage->getReport() === $this) {
                $toppage->setReport(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @ORM\PreFlush()
     */
    public function updateStatus()
    {
        $status = 0;
        try {
            $status += (!is_null($this->globalRank)) ? 6 : 0;
            $status += (!is_null($this->globalRankChange)) ? 6 : 0;
            $status += (!is_null($this->engageRate)) ? 6 : 0;
            $status += (!is_null($this->engageRateChange)) ? 6 : 0;
            $status += (!is_null($this->dailyPageView)) ? 6 : 0;
            $status += (!is_null($this->dailyPageViewChange)) ? 6 : 0;
            $status += (!is_null($this->dailyTimeOnSite)) ? 6 : 0;
            $status += (!is_null($this->dailyTimeOnSiteChange)) ? 6 : 0;
            $status += (!is_null($this->searchVisit)) ? 6 : 0;
//            $status += (!is_null($this->searchVisitChange)) ? 6 : 0;
            $status += (!is_null($this->totalSiteLinking)) ? 6 : 0;
//            $status += (!is_null($this->description)) ? 6 : 0;
//            $status += (!is_null($this->loadSpeed)) ? 6 : 0;
            $status += ($this->geographies->count() > 0) ? 10 : 0;
            $status += ($this->keywords->count() > 0) ? 10 : 0;
            $status += ($this->upstreams->count() > 0) ? 10 : 0;
            $status += ($this->downstreams->count() > 0) ? 10 : 0;
//            $status += ($this->backlinks->count() > 0) ? 10 : 0;
//            $status += ($this->toppages->count() > 0) ? 10 : 0;
        } catch (\Exception $e) {
        }

        $this->setStatus($status);
        if ($domain = $this->getDomain()) {
            $domain->setLastReportQuality($status);
        }
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
