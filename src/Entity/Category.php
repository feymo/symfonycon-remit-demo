<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Six fixed categories, loaded by fixtures (no CRUD on purpose).
 */
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 60)]
        private string $label,

        /** Icon key used by templates/_partials/_icons.html.twig: plane, bed, car, utensils, cup, box */
        #[ORM\Column(length: 20)]
        private string $icon,

        #[ORM\Column]
        private int $position = 0,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
