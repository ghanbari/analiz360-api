<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"template:read"}},
 *     denormalizationContext={"groups"={"template:write"}},
 *     attributes={"pagination_enabled"=false},
 *     itemOperations={
 *          "get",
 *          "put"={
 *              "access_control"="!previous_object.isLocked() && is_granted('ROLE_ADMIN')",
 *              "access_control_message"="You can not update messages that have been sent or times are past.",
 *          },
 *          "delete"={
 *              "access_control"="!object.isLocked()",
 *              "access_control_message"="You can not delete messages that have been sent or times are past.",
 *          },
 *     },
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\EmailTemplateRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"name"})
 */
class EmailTemplate
{
    use TimestampableEntity;
    use BlameableEntity;

    /**
     * @var int the template id
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"template:read"})
     */
    private $id;

    /**
     * @var string the template name
     *
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     *
     * @Groups({"template:read", "template:write", "template:name"})
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     *
     * @Assert\NotBlank()
     * @Assert\Length(max="1048576")
     *
     * @Groups({"template:read", "template:write"})
     */
    private $template;

    /**
     * @ORM\Column(type="json")
     *
     * @Groups({"template:read"})
     */
    private $parameters = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $locked;

    /**
     * EmailTemplate constructor.
     *
     * @param string $name
     * @param $template
     * @param bool $locked
     */
    public function __construct($name = '', $template = null, $locked = false)
    {
        $this->name = $name;
        $this->template = $template;
        $this->locked = $locked;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateParameters()
    {
        $parameters = [];
        preg_match_all('/{([\w.]+)}/m', $this->template, $parameters);
        $user = [
            'user_username',
            'user_password',
            'user_email',
            'user_phone',
            'user_firstName',
            'user_lastName',
            'user_sex',
            'user_city',
        ];

        $this->parameters = array_merge($parameters[1], $user);
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

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    public function getLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;

        return $this;
    }
}
