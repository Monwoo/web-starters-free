<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Form;

use App\Entity\BillingConfig;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BillingConfigSubmitableType extends BillingConfigType
{
    // https://symfony.com/doc/current/reference/constraints/UniqueEntity.html
    // =>ovewrite only the 'unique' part of clientSlug for custom validators on it ?
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // https://stackoverflow.com/questions/74007075/symfony-form-type-extension-for-custom-types
        // https://stackoverflow.com/questions/11237511/multiple-ways-of-calling-parent-method-in-php
        parent::buildForm($builder, $options);
        $builder->add('submit', SubmitType::class, ['label' => 'Mettre à jour']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            // https://stackoverflow.com/questions/74771211/form-dont-accept-null-value-symfony-5
            // 'empty_data'   => "",
            // 'compound'   => false, // You cannot add children to a simple form. Maybe you should set the option "compound" to true?
            'data_class' => BillingConfig::class,
        ]);
    }
}

