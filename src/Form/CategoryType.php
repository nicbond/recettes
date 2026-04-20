<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'empty_data' => '',
                'label' => 'form.category.name',
                'required' => true,
                'translation_domain' => 'form',
            ])
            ->add('slug', TextType::class, [
                'label' => 'form.category.slug',
                'required' => false,
                'help' => 'form.category.helpSlug',
                'help_attr' => ['class' => 'form-text text-muted'],
                'translation_domain' => 'form',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
