<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Tag;

final class TagFilterDTO
{
    /** @var list<Tag> */
    public array $tags = [];
}
