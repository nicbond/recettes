<?php

namespace App\Form;

use App\Entity\Recipe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
            ->add('thumbnailForm', RecipeThumbnailType::class, [
                'label' => false,
                'inherit_data' => true,
            ])
            ->add('category', CategoryAutocompleteField::class, [
                'label' => 'form.recipe.category',
                'required' => true,
                'translation_domain' => 'form',
            ])
            ->add('tags', TagAutocompleteField::class, [
                'label' => 'form.recipe.tag',
                'required' => false,
                'translation_domain' => 'form',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'form.recipe.content',
                'required' => true,
                'translation_domain' => 'form',
                'attr' => [
                    'maxlength' => 2000,
                ],
            ])
            ->add('duration', IntegerType::class, [
                'required' => true,
                'label' => 'form.recipe.duration',
                'translation_domain' => 'form',
                'attr' => [
                    'placeholder' => 'Ex: 5',
                ],
            ])
            ->add('online', CheckboxType::class, [
                'label' => 'form.recipe.online',
                'required' => false,
                'translation_domain' => 'form',
            ])
            ->add('quantities', CollectionType::class, [
                'entry_type' => QuantityType::class,
                'by_reference' => false, // 'false' means we do not want it to modify the collection (use method add and remove)
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options' => ['label' => false],
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
