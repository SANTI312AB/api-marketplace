<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;


class DocumentosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('selfie',FileType::class,[
            "mapped"=>false,
            'constraints' => [
                new File([
                    'maxSize' => '2500k',
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/jpg',
                        'image/webp',
                        'image/png',
                    ],
                    'mimeTypesMessage' => 'Por favor, cargar  imágenes con formato png,jpg o webp ',
                ]),

            
            ]

            ])

            ->add('foto_documento',FileType::class,[
                "mapped"=>false,
                'constraints' => [
                    new File([
                        'maxSize' => '2500k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/webp',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Por favor, cargar  imágenes con formato png,jpg o webp ',
                    ]),

                    ]

            ])


            ->add('avatar',FileType::class,[
                "mapped"=>false,
                'constraints' => [
                    new File([
                        'maxSize' => '2500k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/webp',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Por favor, cargar  imágenes con formato png,jpg o webp ',
                    ]),

                    ]

            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
           /* 'data_class' => Usuarios::class,*/
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }


}
