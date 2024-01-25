<?php

namespace App\Form;

use App\Entity\BillingConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BillingConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clientSlug')
            ->add('clientName')
            ->add('documentType')
            ->add('quotationTemplate')
            ->add('quotationNumber')
            ->add('quotationSourceNumber')
            ->add('quotationAmount')
            ->add('clientEmail')
            ->add('clientTel')
            ->add('clientSIRET')
            ->add('clientTvaIntracom')
            ->add('clientAddressL1')
            ->add('clientAddressL2')
            ->add('clientWebsite')
            ->add('clientLogoUrl')
            ->add('businessLogo')
            ->add('businessWorkloadHours')
            ->add('businessAim')
            ->add('businessWorkloadDetails')
            ->add('quotationStartDay')
            ->add('quotationEndDay')
            ->add('percentDiscount')
            ->add('marginBeforeStartItem')
            ->add('marginAfterStartItem')
            ->add('pageBreakAfterStartItem')
            ->add('marginBeforeEndItem')
            ->add('marginAfterEndItem')
            ->add('pageBreakAfterEndItem')
            ->add('marginBeforeTotal')
            ->add('hideDefaultOutlaysOnEmptyOutlays')
            ->add('outlays')
            ->add('products')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BillingConfig::class,
        ]);
    }
}
