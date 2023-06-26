<?php

namespace App\Form;

use App\Entity\Outlay;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OutlayType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('providerName')
            ->add('percentOnBusinessTotal')
            ->add('taxesPercentIncludedInPercentOnBusinessTotal')
            ->add('providerTotalWithTaxesForseenForClient')
            ->add('providerAddedPrice')
            ->add('providerAddedPriceTaxes')
            ->add('providerAddedPriceTaxesPercent')
            ->add('useProviderAddedPriceForBusiness')
            ->add('providerShortDescription')
            ->add('insertPageBreakBefore')
            ->add('insertPageBreakAfter')
            ->add('providerDetails')
            ->add('billingConfigs')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Outlay::class,
        ]);
    }
}
