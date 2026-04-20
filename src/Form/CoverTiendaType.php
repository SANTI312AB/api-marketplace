<?php

namespace App\Form;


use App\Entity\Tiendas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class CoverTiendaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
       
            ->add('cover',FileType::class,[
                'required' => false, // Hacer opcional
                "mapped"=>false,
                'constraints' => [
                    new File([
                        'maxSize' => '1500k',
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

            ->add('main',FileType::class,[
                'required' => false, // Hacer opcional
                "mapped"=>false,
            'constraints' => [
                new File([
                    'maxSize' => '1500k',
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/jpg',
                        'image/webp',
                        'image/png',
                    ],
                    'mimeTypesMessage' => 'Por favor, cargar  imágenes con formato png,jpg o webp ',
                ])
            
             ]
            ])

      
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            //'data_class' => Tiendas::class,
        ]);
    }


    public function getBlockPrefix():string
    {
        return '';
    }
}


