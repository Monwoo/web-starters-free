<?php

namespace MWS\MoonManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MwsOfferImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('importedUpload', FileType::class, [
            'label' => 'Import file',
            'mapped' => false,
            'required' => false,
            'constraints' => [
                new File([
                    'maxSize' => '40M',
                    'mimeTypes' => [
                        'application/json',
                        'text/json',
                        'application/csv',
                        'text/csv',
                        // https://github.com/symfony/symfony/issues/39237
                        // With php 8 finfo returns application/csv as mime type for csv
                        // With php 7.4 csv files are recognized as text/plain and the guessed extension is txt
                        'text/plain', // Will allow Yaml and CSV... (and all textual format...)
                    ],
                    'mimeTypesMessage' => 'Please upload a valid document',
                ])
            ],
        ]);

        $builder->add('submit', SubmitType::class, ['label' => 'Importer le fichier']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
