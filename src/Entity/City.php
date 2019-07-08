<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CityRepository")
 * @ApiResource(
 *     attributes={"pagination_enabled"=false},
 *     collectionOperations={
 *          "get",
 *     },
 *     itemOperations={"get"},
 *     normalizationContext={"groups"={"city", "city:province", "city:county"}, "enable_max_depth"=true}
 * )
 */
class City
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"city"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     *
     * @Groups({"city"})
     */
    private $name;

    /**
     * @var Province
     *
     * @ORM\ManyToOne(targetEntity="Province", inversedBy="cities")
     * @ORM\JoinColumn(name="province_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     *
     * @Groups({"city:province"})
     * @MaxDepth(1)
     */
    private $province;

    /**
     * @var County
     *
     * @ORM\ManyToOne(targetEntity="County", inversedBy="cities")
     * @ORM\JoinColumn(name="county_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     *
     * @Groups({"city:county"})
     * @MaxDepth(1)
     */
    private $county;

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

    public function getCounty(): ?County
    {
        return $this->county;
    }

    public function setCounty(?County $county): self
    {
        $this->county = $county;

        return $this;
    }
}
