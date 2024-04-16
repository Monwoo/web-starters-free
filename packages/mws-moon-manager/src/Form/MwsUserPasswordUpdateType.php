<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Form;

use MWS\MoonManagerBundle\Entity\MwsUser;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class MwsUserPasswordUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Le mot de passe et la confirmation doivent être identique.',
                'constraints' => [new Length(['min' => 6, 'max' => 30])],
                'label' => false,
                'required' => true,
                'options' => [
                    'attr' => [
                        'class' => 'form-input'
                    ],
                    'label_attr' => [
                        'class' => 'form-label'
                    ]
                ],
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => [
                        'class' => 'form-input extend_inp',
                        'placeholder' => 'Nouveau mot de passe'
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmation du nouveau mot de passe',
                    'attr' => [
                        'class' => 'form-input extend_inp',
                        'placeholder' => 'Confirmation'
                    ]
                ]
            ])//password
            ->add('submit', SubmitType::class, [
                'label' => 'Mettre à jour le mot de passe',
                'attr' => [
                    // 'class' => 'btn btn-outline-success'
                ]
            ])//submit
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MwsUser::class,
        ]);
    }
}
