<?php

namespace App\Form;

use App\DTO\CategoryFilterDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', CategoryAutocompleteField::class, [
                'label' => false,
                'required' => false,
                'placeholder' => '🔍 Rechercher une catégorie...',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CategoryFilterDTO::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
