<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Form;

use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class MwsUserFilterType extends MwsSurveyJsType
{
  public function __construct(
    protected Security $security,
    protected UserRepository $userRepository,
  ) {
  }

  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    parent::buildForm($builder, $options);
    // TODO : ? https://symfony.com/doc/current/reference/forms/types/search.html#help-attr 
    // {{ form_help(form.name, 'Your name', {
    //     'help_attr': {'class': 'CUSTOM_LABEL_CLASS'}
    // }) }}

    // $builder->add('submit', SubmitType::class, [
    //   'label' => 'Rechercher dans les utilisateurs',
    //   'row_attr' => [
    //     'class' => 'md:w-1/4 justify-center self-end'
    //   ],
    // ]);
  }
}
