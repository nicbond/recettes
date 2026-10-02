<?php

namespace App\Form\User;

use App\Entity\User\Admin;
use App\Enum\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class AdminCreateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'translation_domain' => 'form',
                'constraints' => [
                    new NotBlank(message: 'form.password.required'),
                    new Length(min: 12, minMessage: 'form.password.length_min'),
                    new Regex(
                        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&^#])[A-Za-z\d@$!%*?&^#]{12,}$/',
                        message: 'form.password.valid'
                    ),
                ],
                'first_options' => ['label' => 'form.password.label'],
                'second_options' => ['label' => 'form.password.confirm'],
            ])
            ->add('roles', ChoiceType::class, [
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'empty_data' => '',
                'translation_domain' => 'form',
                'label' => 'form.admin.role',
                'help' => 'form.admin.help',
                'help_attr' => ['class' => 'form-text text-muted'],
                'choices' => [
                    'form.admin.super_admin' => Role::SUPER_ADMIN->value,
                    'form.admin.admin' => Role::ADMIN->value,
                    'form.admin.user' => Role::USER->value,
                ],
                'attr' => [
                    'placeholder' => 'form.admin.placeholder',
                    'data-placeholder' => 'form.admin.placeholder',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Admin::class,
        ]);
    }
}
