<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Form;

use MWS\MoonManagerBundle\Entity\MwsMessageTchatUpload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class MwsMessageTchatUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // $builder->add('mediaFile', VichImageType::class, [
        //     'required' => false
        // ]);
        $builder->add('mediaFile', VichFileType::class, [
            'required' => false
        ]);


        $builder->add('submit', SubmitType::class, ['label' => 'Télécharger']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MwsMessageTchatUpload::class,
            // 'csrf_protection' => false, // WARNING : do your own csrf ways if false...
        ]);
    }
}
