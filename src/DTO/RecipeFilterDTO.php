<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Recipe\Category;
use App\Entity\Recipe\Tag;

final class RecipeFilterDTO
{
    public ?string $title = null;

    public ?Category $category = null;

    /** @var list<Tag> */
    public array $tags = [];
}
