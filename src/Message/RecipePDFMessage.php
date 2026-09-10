<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async-pdf')]
final readonly class RecipePDFMessage
{
    public function __construct(public int $recipeId)
    {
    }
}
