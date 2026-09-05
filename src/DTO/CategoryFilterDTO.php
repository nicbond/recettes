<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Category;

final class CategoryFilterDTO
{
    public ?Category $category = null;
}
