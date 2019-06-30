<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CountyRepository")
 * @ApiResource(
 *     attributes={"pagination_enabled"=false},
 *     collectionOperations={
 *          "get"
 *     },
 *     itemOperations={"get"}
 * )
 */
class County
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $name;

    /**
     * @var Province
     *
     * @ORM\ManyToOne(targetEntity="Province", inversedBy="counties")
     * @ORM\JoinColumn(name="province_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $province;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="City", mappedBy="county")
     *
     * @ApiSubresource()
     */
    private $cities;

    /**
     * County constructor.
     */
    public function __construct()
    {
        $this->cities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(?Province $province): self
    {
        $this->province = $province;

        return $this;
    }

    /**
     * @return Collection|City[]
     */
    public function getCities(): Collection
    {
        return $this->cities;
    }

    /**
     * @param ArrayCollection $cities
     */
    public function setCities(ArrayCollection $cities): void
    {
        $this->cities = $cities;
    }

    public function addCity(City $city): self
    {
        if (!$this->cities->contains($city)) {
            $this->cities[] = $city;
            $city->setCounty($this);
        }

        return $this;
    }

    public function removeCity(City $city): self
    {
        if ($this->cities->contains($city)) {
            $this->cities->removeElement($city);
            // set the owning side to null (unless already changed)
            if ($city->getCounty() === $this) {
                $city->setCounty(null);
            }
        }

        return $this;
    }
}
