<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogRepository")
 */
class Log
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $endTime;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $updatedDomainCount;

    /**
     * @var int total time in second
     *
     * @ORM\Column(type="integer")
     */
    private $totalTime;

    /**
     * @var int memory usage
     *
     * @ORM\Column(type="integer")
     */
    private $memory;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getUpdatedDomainCount(): ?int
    {
        return $this->updatedDomainCount;
    }

    public function setUpdatedDomainCount(int $updatedDomainCount): self
    {
        $this->updatedDomainCount = $updatedDomainCount;

        return $this;
    }

    public function getTotalTime(): ?int
    {
        return $this->totalTime;
    }

    public function setTotalTime(int $totalTime): self
    {
        $this->totalTime = $totalTime;

        return $this;
    }

    public function getMemory(): ?int
    {
        return $this->memory;
    }

    public function setMemory(int $memory): self
    {
        $this->memory = $memory;

        return $this;
    }
}
