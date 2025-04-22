<?php

namespace App\Entity;

use App\Repository\SubCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubCategoryRepository::class)]
class SubCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['subcategory:read', 'category:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.')]
    #[Groups(['subcategory:read', 'category:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['subcategory:read', 'category:read'])]
    private int $displayOrder = 0;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type(\DateTimeImmutable::class)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'subCategories')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La catégorie est obligatoire.')]
    #[Groups(['subcategory:read', 'dish:read'])]
    private ?Category $category = null;

    /**
     * @var Collection<int, Dishe>
     */
    #[ORM\OneToMany(targetEntity: Dishe::class, mappedBy: 'subCategory')]
    #[Groups(['subcategory:read', 'category:read'])]
    private Collection $dishes;

    public function __construct()
    {
        $this->dishes = new ArrayCollection();
    }

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

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $order): static
    {
        $this->displayOrder = $order;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Collection<int, Dishe>
     */
    public function getDishes(): Collection
    {
        return $this->dishes;
    }

    public function addDish(Dishe $dish): static
    {
        if (!$this->dishes->contains($dish)) {
            $this->dishes->add($dish);
            $dish->setSubCategory($this);
        }

        return $this;
    }

    public function removeDish(Dishe $dish): static
    {
        if ($this->dishes->removeElement($dish) && $dish->getSubCategory() === $this) {
            $dish->setSubCategory(null);
        }

        return $this;
    }
}
