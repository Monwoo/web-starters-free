<?php

namespace App\Form;

use App\Entity\Product;
use NumberFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label')
            ->add('quantity', NumberType::class, array(
                'rounding_mode' => NumberFormatter::ROUND_HALFUP,
                'scale' => 5,
                // 'attr' => array(
                //     'min' => -90,
                //     'max' => 90,
                //     'step' => 0.01,
                // ),
            ))
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
