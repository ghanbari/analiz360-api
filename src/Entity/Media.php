<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\CreateMediaAction;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity
 * @ApiResource(
 *     iri="http://schema.org/MediaObject",
 *     collectionOperations={
 *          "get"={
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          },
 *          "post"={
 *              "method"="POST",
 *              "controller"=CreateMediaAction::class,
 *              "defaults"={"_api_receive"=false},
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          }
 *     },
 *     itemOperations={
 *          "get",
 *          "delete"={
 *              "access_control"="is_granted('ROLE_ADMIN')",
 *          },
 *     },
 *     attributes={
 *          "normalization_context"={"groups"={"media:read", "media:info"}},
 *          "denormalization_context"={"groups"={"media:write"}}
 *     }
 * )
 * @Vich\Uploadable
 */
class Media
{
    use BlameableEntity;
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var File|null
     * @Assert\NotNull()
     * @Vich\UploadableField(
     *     mapping="media",
     *     fileNameProperty="contentUrl",
     *     size="size",
     *     mimeType="mimeType",
     *     dimensions="dimensions",
     *     originalName="originalName"
     * )
     *
     * @Groups({"media:write"})
     */
    private $file;

    /**
     * @var string|null
     * @ORM\Column()
     * @ApiProperty(iri="http://schema.org/contentUrl")
     *
     * @Groups({"media:read"})
     */
    private $contentUrl;

    /**
     * @ORM\Column(name="original_name", nullable=true)
     *
     * @Groups({"media:info"})
     */
    private $originalName;

    /**
     * @ORM\Column(name="mime_type", nullable=true)
     *
     * @Groups({"media:info"})
     */
    private $mimeType;

    /**
     * @ORM\Column(name="size", type="integer", nullable=true)
     *
     * @Groups({"media:info"})
     */
    private $size;

    /**
     * @ORM\Column(name="dimensions", type="simple_array", nullable=true)
     *
     * @Groups({"media:info"})
     */
    private $dimensions;

    public function setFile(?File $file = null)
    {
        $this->file = $file;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContentUrl(): ?string
    {
        return $this->contentUrl;
    }

    public function setContentUrl(string $contentUrl): self
    {
        $this->contentUrl = $contentUrl;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getDimensions(): ?array
    {
        return $this->dimensions;
    }

    public function setDimensions(?array $dimensions): self
    {
        $this->dimensions = $dimensions;

        return $this;
    }
}
