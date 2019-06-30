<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DomainFreeWatchingRepository")
 */
class DomainFreeWatching
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Domain")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $domain;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $watcher;

    /**
     * DomainFreeWatching constructor.
     *
     * @param $domain
     * @param $watcher
     */
    public function __construct($domain, $watcher)
    {
        $this->domain = $domain;
        $this->watcher = $watcher;
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

    public function getWatcher(): ?User
    {
        return $this->watcher;
    }

    public function setWatcher(?User $watcher): self
    {
        $this->watcher = $watcher;

        return $this;
    }
}
