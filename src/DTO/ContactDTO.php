<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\ServiceEnum;
use Symfony\Component\Validator\Constraints as Assert;

class ContactDTO
{
    #[Assert\NotBlank(
        message: 'Le nom ne doit pas être vide.',
        normalizer: 'trim'
    )]
    #[Assert\Length(
        min: 3,
        minMessage: 'Le nom doit être supèrieur à {{ limit }} caractères.',
    )]
    public string $name = '';

    #[Assert\NotBlank(
        message: "L'adresse email ne doit pas être vide.",
        normalizer: 'trim'
    )]
    #[Assert\Email(
        message: "L'adresse email '{{ value }}' n'est pas valide.",
        mode: 'strict',
        normalizer: 'trim'
    )]
    public string $email = '';

    #[Assert\NotBlank(
        message: 'Le téléphone ne doit pas être vide.',
        normalizer: 'trim'
    )]
    #[Assert\Regex(
        pattern: '/^((\+)33|0)[1-9](\d{2}){4}$/',
        message: 'Le numéro de téléphone n\'est pas valide.',
    )]
    public string $phone = '';

    #[Assert\NotBlank(message: 'Veuillez sélectionner un service.')]
    #[Assert\Choice(callback: [ServiceEnum::class, 'values'])]
    public ?string $service = null;

    #[Assert\NotBlank(
        message: 'Le contenu de votre message ne peut pas être vide.',
    )]
    #[Assert\Length(
        min: 3,
        minMessage: 'Le message doit être supèrieur à {{ limit }} caractères.',
    )]
    public string $message = '';
}
