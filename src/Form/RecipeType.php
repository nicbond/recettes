<?php

namespace App\Form;

use App\Entity\Recipe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'form.recipe.title',
                'required' => true,
                'translation_domain' => 'form',
            ])
            ->add('slug', TextType::class, [
                'label' => 'form.recipe.slug',
                'required' => false,
                'help' => 'form.recipe.helpSlug',
                'help_attr' => ['class' => 'form-text text-muted'],
                'translation_domain' => 'form',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'form.recipe.content',
                'translation_domain' => 'form',
            ])
            ->add('duration', IntegerType::class, [
                'required' => false,
                'label' => 'form.recipe.duration',
                'translation_domain' => 'form',
                'attr' => [
                    'placeholder' => 'Ex: 5',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
            'validation_groups' => ['Default', 'Extra'],
        ]);
    }
}
