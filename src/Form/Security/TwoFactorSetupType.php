<?php

declare(strict_types=1);

namespace App\Form\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class TwoFactorSetupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', TextType::class, [
            'label' => 'Code de vérification',
            'required' => true,
            'attr' => [
                'autocomplete' => 'one-time-code',
                'inputmode' => 'numeric',
                'maxlength' => 6,
                'pattern' => '[0-9]{6}',
            ],
            'constraints' => [
                new Assert\NotBlank(
                    message: 'Le code est obligatoire.'
                ),
                new Assert\Regex(
                    pattern: '/^\d{6}$/',
                    message: 'Le code doit contenir exactement 6 chiffres.'
                ),
            ],
        ]);
    }
}
