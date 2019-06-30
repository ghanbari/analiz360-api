<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProvinceRepository")
 * @ApiResource(
 *     attributes={"pagination_enabled"=false},
 *     collectionOperations={
 *          "get"={"normalization_context"={"groups"={"province"}}},
 *          "api_provinces_counties_get_subresource"={
 *              "pagination_enabled"=false,
 *              "method"="get",
 *              "normalization_context"={"groups"={"county", "province"}},
 *          },
 *          "api_provinces_cities_get_subresource"={
 *              "pagination_enabled"=false,
 *              "method"="get",
 *              "normalization_context"={"groups"={"city"}, "enable_max_depth"=true}
 *          },
 *      },
 *     itemOperations={"get"},
 * )
 */
class Province
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"province"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     *
     * @Groups({"province"})
     */
    private $name;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="County", mappedBy="province")
     *
     * @ApiSubresource(maxDepth=1)
     *
     * @Groups({"province:county"})
     */
    private $counties;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="City", mappedBy="province")
     *
     * @ApiSubresource(maxDepth=1)
     *
     * @Groups({"province:cities"})
     * @MaxDepth(1)
     */
    private $cities;

    /**
     * Province constructor.
     */
    public function __construct()
    {
        $this->counties = new ArrayCollection();
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

    /**
     * @return Collection|County[]
     */
    public function getCounties(): Collection
    {
        return $this->counties;
    }

    public function addCounty(County $county): self
    {
        if (!$this->counties->contains($county)) {
            $this->counties[] = $county;
            $county->setProvince($this);
        }

        return $this;
    }

    public function removeCounty(County $county): self
    {
        if ($this->counties->contains($county)) {
            $this->counties->removeElement($county);
            // set the owning side to null (unless already changed)
            if ($county->getProvince() === $this) {
                $county->setProvince(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|City[]
     */
    public function getCities(): Collection
    {
        return $this->cities;
    }

    public function addCity(City $city): self
    {
        if (!$this->cities->contains($city)) {
            $this->cities[] = $city;
            $city->setProvince($this);
        }

        return $this;
    }

    public function removeCity(City $city): self
    {
        if ($this->cities->contains($city)) {
            $this->cities->removeElement($city);
            // set the owning side to null (unless already changed)
            if ($city->getProvince() === $this) {
                $city->setProvince(null);
            }
        }

        return $this;
    }
}
