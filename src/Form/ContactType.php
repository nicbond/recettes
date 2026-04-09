<?php

namespace App\Form;

use App\DTO\ContactDTO;
use App\Enum\ServiceEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'empty_data' => '',
                'label' => 'form.contact.name',
                'translation_domain' => 'form',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'empty_data' => '',
                'label' => 'form.contact.email',
                'translation_domain' => 'form',
                'required' => true,
            ])
            ->add('service', ChoiceType::class, [
                'label' => 'form.contact.service.label',
                'translation_domain' => 'form',
                'required' => true,
                'empty_data' => '',
                'choices' => [
                    'form.contact.technical.service' => ServiceEnum::TECHNIQUE->value,
                    'form.contact.comptability.service' => ServiceEnum::COMPTABLE->value,
                    'form.contact.rh.service' => ServiceEnum::RH->value,
                ],
                'placeholder' => 'form.contact.service.placeholder',
            ])
            ->add('message', TextareaType::class, [
                'empty_data' => '',
                'label' => 'form.contact.message',
                'translation_domain' => 'form',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactDTO::class,
        ]);
    }
}
