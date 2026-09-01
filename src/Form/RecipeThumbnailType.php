<?php

namespace App\Form;

use App\Entity\Recipe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RecipeThumbnailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('thumbnailFile', FileType::class, [
            'label' => 'Image de la recette',
            'required' => false,
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
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}
