<?php
// TIPS : Auto generated file, might be re-writed with :
// rm src/Form/MwsUserBaseType.php
// php bin/console make:form MwsUserBaseType MwsUser
namespace MWS\MoonManagerBundle\Form;

use MWS\MoonManagerBundle\Entity\MwsUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MwsUserBaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('email')
            ->add('phone')
            ->add('description')
            ->add('roles')
            ->add('password')
            ->add('teamMembers')
            ->add('teamOwners')
            ->add('mwsClientEvents')
            ->add('mwsObserverEvents')
            ->add('mwsOwnerEvents')
            ->add('mwsCalendarTrackings')
            ->add('createdAt')
            ->add('updatedAt')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MwsUser::class,
        ]);
    }
}
