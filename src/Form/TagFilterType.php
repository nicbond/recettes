<?php

namespace App\Form;

use App\DTO\TagFilterDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tags', TagAutocompleteField::class, [
                'label' => false,
                'required' => false,
                'placeholder' => '🔍 Rechercher un ou des tags...',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TagFilterDTO::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
