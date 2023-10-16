<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Form;

use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Repository\MwsUserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MwsUserType extends AbstractType
{
    public function __construct(
        protected Security $security,
        protected MwsUserRepository $mwsUserRepository,
    ){
        // Minimum user edit capabilities for default template.
        // fields will come up with user roles
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MwsUser::class,
        ]);
    }
}
