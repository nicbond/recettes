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
                'label' => 'Titre',
                'required' => true
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug <br><span style="color: #888; font-weight: normal;">Si vous souhaitez spécifier votre slug, renseignez-le sinon il se créera automatiquement à partir du titre</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Descriptif',
            ])
            ->add('duration', IntegerType::class, [
                'required' => false,
                'label' => 'Durée',
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
            'validation_groups' => ['Default', 'Extra']
        ]);
    }
}
