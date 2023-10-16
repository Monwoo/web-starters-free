<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Form;

use Doctrine\ORM\QueryBuilder;
use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Repository\MwsUserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class MwsUserAdminType extends MwsUserBaseType
{
    public function __construct(
        protected LoggerInterface $logger,
        protected Security $security,
        protected MwsUserRepository $mwsUserRepository,
    ){
        // Minimum user edit capabilities for default template.
        // fields will come up with user roles
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $shouldAddNew = $options['shouldAddNew'] ?? false;
        $user = $this->security->getUser();
        $availableRoles = $this->mwsUserRepository->getAvailableRoles();
        $targetUser = $options['targetUser'] ?? false;
        $teamMembersRolesFilter = $options['teamMembersRolesFilter'] ?? []; // No filter for admin;

        if (!in_array(MwsUser::$ROLE_ADMIN, $user->getRoles())) {
            $this->logger->warning("Missing [" . MwsUser::$ROLE_ADMIN . "]");
            return; // Not an Admin, show nothing
        }

        parent::buildForm($builder, $options);

        $builder->remove('password'); // no need to edit hashed password

        if ($shouldAddNew) {
            $builder->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Le mot de passe et la confirmation doivent être identique.',
                'constraints' => [new Length(['min' => 6, 'max' => 30])],
                'label' => false,
                'mapped' => false,
                'required' => $shouldAddNew, // Always needed for NEW form 
                'options' => [
                    'attr' => [
                        'class' => 'form-input'
                    ],
                    'label_attr' => [
                        'class' => 'form-label'
                    ]
                ],
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'class' => 'form-input',
                        'placeholder' => 'Mot de passe'
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmation du mot de passe',
                    'attr' => [
                        'class' => 'form-input',
                        'placeholder' => 'Confirmation du mot de passe'
                    ]
                ]
            ]); //password
        }

        $builder->add('roles', ChoiceType::class, [
            'multiple' => true,
            'choices'  => array_flip($availableRoles),
            'label' => 'Profil(s)',
            // TODO : CTRL ok for multi-select under windows ?
            'help' => 'Multi-selection en maintenant la touche "CTRL" puis en cliquant pour Windows. Touche "Command" pour MacOsX.',
            // 'placeholder' => 'votre choix',
            'required' => false,
            'label_attr' => [
                'class' => 'form-label'
            ],
            'attr' => [
                // 'style' => '',
                'class' => 'form-input',
                'placeholder' => 'Roles'
            ],
        ])
        ->add('teamMembers', EntityType::class, [
            'class' => MwsUser::class,
            'label' => "Membres de l'équipe",
            'help' => 'Multi-selection en maintenant la touche "CTRL" puis en cliquant pour Windows. Touche "Command" pour MacOsX.',
            // TODO : CTRL ok for multi-select under windows ?
            'placeholder' => 'votre choix',
            'required' => false,
            'query_builder' => function (MwsUserRepository $er)
            use ($teamMembersRolesFilter, $targetUser) : QueryBuilder {
                return ($er->teamMembersQuery)($teamMembersRolesFilter, $targetUser);
            },
            'choice_label' => $this->mwsUserRepository->teamMemberschoiceLabelHandler,
            'choice_value' => function ($user): string {
                return $user ? $user->getId() : '';
            },
            'multiple' => true,
            // used to render a select box, check boxes or radios
            // 'expanded' => true,
            'label_attr' => [
                'class' => 'form-label'
            ],
            'attr' => [
                'style' => 'min-height:5em;',
                'class' => 'form-input',
            ]
        ])
        ->add('teamOwners', EntityType::class, [
            'class' => MwsUser::class,
            'label' => "Chef(s) de l'équipe",
            'help' => 'Multi-selection en maintenant la touche "CTRL" puis en cliquant pour Windows. Touche "Command" pour MacOsX.',
            // TODO : CTRL ok for multi-select under windows ?
            'placeholder' => 'votre choix',
            'required' => false,
            'query_builder' => function (MwsUserRepository $er)
            use ($teamMembersRolesFilter, $targetUser) : QueryBuilder {
                return ($er->teamMembersQuery)($teamMembersRolesFilter, $targetUser);
            },
            'choice_label' => $this->mwsUserRepository->teamMemberschoiceLabelHandler,
            'choice_value' => function ($user): string {
                return $user ? $user->getId() : '';
            },
            'multiple' => true,
            // used to render a select box, check boxes or radios
            // 'expanded' => true,
            'label_attr' => [
                'class' => 'form-label'
            ],
            'attr' => [
                'style' => 'min-height:5em;',
                'class' => 'form-input',
            ]
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'Enregistrer',
            'attr' => [
                'class' => 'btn'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'shouldAddNew' => true,
            'targetUser' => null,
            'teamMembersRolesFilter' => null,
        ]);
    }

}
