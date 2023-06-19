<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class BillingConfigSubmitableType extends BillingConfigType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // https://stackoverflow.com/questions/74007075/symfony-form-type-extension-for-custom-types
        // https://stackoverflow.com/questions/11237511/multiple-ways-of-calling-parent-method-in-php
        parent::buildForm($builder, $options);
        $builder->add('submit', SubmitType::class, ['label' => 'Mettre à jour']);
    }
}

