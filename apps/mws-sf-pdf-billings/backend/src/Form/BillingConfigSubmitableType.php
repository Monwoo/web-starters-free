<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Form;

use App\Entity\BillingConfig;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class BillingConfigSubmitableType extends BillingConfigType
{
    // https://symfony.com/doc/current/reference/constraints/UniqueEntity.html
    // =>ovewrite only the 'unique' part of clientSlug for custom validators on it ?
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // https://symfony.com/doc/current/controller/upload_file.html
        // https://symfony.com/doc/current/reference/constraints/File.html
        $builder->add('importedUpload', FileType::class, [
                'label' => 'Import (YAML/JSON/CSV/XML)',
                // unmapped means that this field is not associated to any entity property
                'mapped' => false,
                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,
                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            // https://www.iana.org/assignments/media-types/media-types.xhtml
                            // 'application/pdf', // TODO : import PDF ? qrCode extract and keep md5 linked to all exports somewhere ?
                            // 'application/x-pdf',
                            'text/xml',
                            'application/xml',
                            'application/json',
                            'application/yaml',
                            'application/csv',
                            'text/csv',
                            // https://github.com/symfony/symfony/issues/39237
                            // With php 8 finfo returns application/csv as mime type for csv
                            // With php 7.4 csv files are recognized as text/plain and the guessed extension is txt
                            'text/plain', // Will allow Yaml and CSV... (and all textual format...)
                        ],
                        'mimeTypesMessage' => 'Please upload a valid yaml/json/csv/xml document',

                        // The extensions option was introduced in Symfony 6.2. (recommended way after 6.2....)
                        // 'extensions' => [ 'yaml', 'yml', 'json', 'csv', 'xml', 'pdf' ],
                        // 'extensionsMessage' => 'Please upload a valid yaml/json/csv/xml document',
                    ])
                ],
            ]);

        // https://stackoverflow.com/questions/74007075/symfony-form-type-extension-for-custom-types
        // https://stackoverflow.com/questions/11237511/multiple-ways-of-calling-parent-method-in-php
        parent::buildForm($builder, $options);
        $builder->add('documentType', ChoiceType::class, [
            'choices'  => [
                'Devis' => 'devis',
                'Proforma' => 'proforma',
                'Facture' => 'facture',
            ],
        ]);

        // $builder->add(
        $builder->add('quotationStartDay',
            // DateTimeTzType::class,
            DateTimeType::class,
            array(
                'required' => false,
                'widget' => 'single_text',
                // 'format' => 'yyyy-MM-dd  HH:mm',
                // https://ourcodeworld.com/articles/read/1182/how-to-solve-symfony-5-exception-cannot-use-the-format-option-of-symfony-component-form-extension-core-type-datetype-when-the-html5-option-is-enabled
                // 'html5' => false,
                'html5' => true,
            )
        );

        $builder->add('quotationEndDay',
            DateTimeType::class,
            array(
                'required' => false,
                'widget' => 'single_text',
                // 'format' => 'yyyy-MM-dd  HH:mm',
                'html5' => true,
            )
        );

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
                'Monwoo Maintenance simple' => 'monwoo-07-upkeep',
                'Monwoo Formation backend fullstack' => 'monwoo-08-backend-learning',
                'Monwoo template vide' => 'monwoo-09-empty',
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

        $builder->add('transactions', CollectionType::class, [
            'entry_type' => TransactionType::class,
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

