<?php

namespace App\Form;

use App\Entity\Recipe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

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
            ->add('thumbnailFile', FileType::class, [
                'label' => 'form.recipe.thumbnailFile',
                'constraints' => [
                    new Assert\File(
                        maxSize: '7000k',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        maxSizeMessage: 'Le fichier est trop volumineux',
                        mimeTypesMessage: 'Veuillez uploader une image au format jpeg, png ou webp.',
                    ),
                    new Assert\Image(
                        maxWidth: 1080,
                        maxHeight: 1080,
                    ),
                ],
                'required' => false,
                'translation_domain' => 'form',
            ])
            ->add('category', CategoryAutocompleteField::class, [
                'label' => 'form.recipe.category',
                'required' => true,
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
