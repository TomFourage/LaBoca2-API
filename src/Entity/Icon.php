<?php

namespace App\Entity;

use App\Repository\IconRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: IconRepository::class)]
class Icon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['icon:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['icon:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['icon:read'])]
    private ?string $class = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(string $class): static
    {
        $this->class = $class;

        return $this;
    }
}
