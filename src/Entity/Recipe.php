<?php

namespace App\Entity;

use App\Repository\RecipeRepository;
use App\Validator\BanWord;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: RecipeRepository::class)]
#[UniqueEntity(
    fields: ['title'],
    message: 'Ce titre existe déjà.'
)]
#[UniqueEntity(
    fields: ['slug'],
    message: 'Ce slug existe déjà.'
)]
class Recipe
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\Length(
        min: 5,
        minMessage: 'Le titre doit être supèrieur à {{ limit }} caractères.',
        groups: ['Extra']
    )]
    #[BanWord(groups: ['Extra'])]
    private string $title;

    #[ORM\Column(length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['title'])]
    #[Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Invalid slug')]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(
        min: 5,
        minMessage: 'Le descriptif doit être supèrieur à {{ limit }} caractères.'
    )]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    #[Assert\LessThan(value: 1440)]
    private ?int $duration = null;

    #[ORM\ManyToOne(inversedBy: 'recipes')]
    private ?Category $category = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private EmbeddedFile $thumbnail;

    #[Vich\UploadableField(mapping: 'recipes', fileNameProperty: 'thumbnail.name', size: 'thumbnail.size', mimeType: 'thumbnail.mimeType', originalName: 'thumbnail.originalName')]
    #[Assert\Image()]
    private ?File $thumbnailFile = null;

    /**
     * @var Collection<int, Quantity>
     */
    #[ORM\OneToMany(mappedBy: 'recipe', targetEntity: Quantity::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $quantities;

    public function __construct()
    {
        $this->thumbnail = new EmbeddedFile();
        $this->quantities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getThumbnail(): EmbeddedFile
    {
        return $this->thumbnail;
    }

    public function setThumbnail(EmbeddedFile $thumbnail): static
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getThumbnailFile(): ?File
    {
        return $this->thumbnailFile;
    }

    public function setThumbnailFile(?File $thumbnailFile): static
    {
        $this->thumbnailFile = $thumbnailFile;

        if (null !== $thumbnailFile) {
            // Mandatory: triggers update for VichUploader
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

    /**
     * @return Collection<int, Quantity>
     */
    public function getQuantities(): Collection
    {
        return $this->quantities;
    }

    public function addQuantity(Quantity $quantity): static
    {
        if (!$this->quantities->contains($quantity)) {
            $this->quantities->add($quantity);
            $quantity->setRecipe($this);
        }

        return $this;
    }

    public function removeQuantity(Quantity $quantity): static
    {
        if ($this->quantities->removeElement($quantity)) {
            // set the owning side to null (unless already changed)
            if ($quantity->getRecipe() === $this) {
                $quantity->setRecipe(null);
            }
        }

        return $this;
    }
}
