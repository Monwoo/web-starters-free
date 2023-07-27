<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label')
            ->add('quantity')
            ->add('pricePerUnitWithoutTaxes')
            ->add('taxesPercent')
            ->add('discountPercent')
            ->add('usedForBusinessTotal')
            ->add('leftTitle')
            ->add('leftDetails')
            ->add('rightDetails')
            ->add('insertPageBreakBefore')
            ->add('marginTop')
            ->add('insertPageBreakAfter')
            ->add('marginBottom')
            ->add('billings')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
