<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CountryRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="IDX_ALPHA_2", columns={"alpha2"}),
 *      }
 * )
 */
class Country
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(length=2)
     */
    private $alpha2;

    /**
     * @var string
     *
     * @ORM\Column(length=3)
     */
    private $alpha3;

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

    public function getAlpha2(): ?string
    {
        return $this->alpha2;
    }

    public function setAlpha2(string $alpha2): self
    {
        $this->alpha2 = $alpha2;

        return $this;
    }

    public function getAlpha3(): ?string
    {
        return $this->alpha3;
    }

    public function setAlpha3(string $alpha3): self
    {
        $this->alpha3 = $alpha3;

        return $this;
    }

    public function __toString()
    {
        return sprintf('id: %d, name: %s, alpha2: %s', $this->getId(), $this->name, $this->alpha2);
    }
}
