<?php

namespace MWS\MoonManagerBundle\Form;

use MWS\MoonManagerBundle\Entity\MwsOfferStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MwsOfferStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('slug')
            ->add('label')
            ->add('categorySlug')
            ->add('bgColor', ColorType::class)
            ->add('textColor', ColorType::class)
            // ->add('mwsOffers')
            // ->add('createdAt')
            // ->add('updatedAt')
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'btn'
                ]
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'shouldAddNew' => true,
            'targetTag' => null,
            'data_class' => MwsOfferStatus::class,
        ]);
    }
}
