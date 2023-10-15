<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MwsSurveyJsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('jsonResult', HiddenType::class, [
            'mapped' => false,
            // 'mapped' => true, // NEEDED if want it to be sync with input data...
            'required' => true,
            // 'constraints' => [
            // ],
            'row_attr' => [
                'class' => 'md:w-1/4'
            ],
        ]);

        $builder->add('surveyJsModel', HiddenType::class, [
            'mapped' => false,
            // 'mapped' => true, // NEEDED if want it to be sync with input data...
            'required' => true,
            // 'constraints' => [
            // ],
            'row_attr' => [
                'class' => 'md:w-1/4'
            ],
        ]);

        // $builder->add('submit', SubmitType::class, ['label' => 'Valider le questionnaire']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // $resolver->setDefaults([
        //     'data_class' => Outlay::class,
        // ]);
    }
}
