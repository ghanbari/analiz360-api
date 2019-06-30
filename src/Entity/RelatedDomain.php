<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RelatedDomainRepository")
 *
 * @deprecated use tags(domain, tag) for finding similar sites
 * @deprecated Remove in future(alexa related domains dont have very accurate)
 */
class RelatedDomain
{
    use TimestampableEntity;

    const STATUS_UNDEFINED = -1;
    const STATUS_INVALID = 0;
    const STATUS_VALID = 1;

    const SOURCE_ALEXA = 'alexa';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Domain
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Domain")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $domain;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $source;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $relatedWith;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $score;

    /**
     * RelatedDomain constructor.
     *
     * @param Domain $domain
     * @param string $relatedWith
     * @param $source
     * @param null $score
     * @param int  $status
     */
    public function __construct(Domain $domain, String $relatedWith, $source, $score = null, $status = self::STATUS_UNDEFINED)
    {
        $this->domain = $domain;
        $this->source = $source;
        $this->relatedWith = $relatedWith;
        $this->score = $score;
        $this->status = $status;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getRelatedWith(): ?string
    {
        return $this->relatedWith;
    }

    public function setRelatedWith(string $relatedWith): self
    {
        $this->relatedWith = $relatedWith;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): self
    {
        $this->score = $score;

        return $this;
    }
}
