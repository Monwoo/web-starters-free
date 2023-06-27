<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Form;

use App\Entity\BillingConfig;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
        // https://symfony.com/doc/current/reference/forms/types/choice.html
        $builder->add('quotationTemplate', ChoiceType::class, [
            // https://symfony.com/doc/current/reference/forms/types/choice.html#choice-name
            // 'choice_value' => ChoiceList::fieldName($this, 'template'),
            // TIPS : Using data option is no good, because:
            // The data option always overrides the value taken from the domain data (object) 
            // TIPS : you can set from form (but will not be saved in database then...)
            // $form = $formBuilder->getForm();
            // $form->get('firstname')->setData('John');
            // + You will need to set "mapped" at false if it's not linked to the data_class
            // "mapped" => false,
            'choices'  => [
                'Monwoo Svelte PWA' => 'monwoo',
                'Monwoo WooCommerce' => 'monwoo-02-wp-e-com',
                'Monwoo PHP backend' => 'monwoo-03-php-backend',
                'Monwoo Fullstack hybrid' => 'monwoo-04-hybrid-app',
                'Monwoo CRM PHP' => 'monwoo-05-php-crm',
                'Monwoo Etude Analytique' => 'monwoo-06-analytical-study',
            ],
        ]);
        // https://symfony.com/doc/current/form/form_collections.html
        $builder->add('outlays', CollectionType::class, [
            'entry_type' => OutlayType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            // 'allow_add' => true,
            // 'prototype' => true,
            // 'entry_options' => ['label' => false],
        ]);

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

