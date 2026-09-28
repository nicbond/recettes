<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\User\Admin;
use App\Enum\Permission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminPermissionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        foreach (Permission::cases() as $permission) {
            $choices[$permission->group()][$permission->label()] = $permission->value;
        }

        $builder->add('permissions', ChoiceType::class, [
            'choices' => $choices,
            'multiple' => true,
            'expanded' => true,
            'label' => 'Permissions',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Admin::class,
        ]);
    }
}
