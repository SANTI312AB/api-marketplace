<?php

namespace App\Form;

use App\Entity\GaleriaTienda;
use App\Entity\Tiendas;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class GaleriaTindaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url',FileType::class,[
                'required'=>true,
                'multiple' => false,

                'constraints' => [
                    new File([
                        
                        'maxSize' => '1500k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/webp',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Por favor, cargar  imágenes con formato png,jpg o webp ',
                    ])
                ],
            ])
            ->add('seccion',ChoiceType::class,[
                'choices'=>[
                    'banners'=>'banners',
                    'sliders'=>'sliders',
                    'promociones'=>'promociones'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
           // 'data_class' => GaleriaTienda::class,
        ]);
    }


    public function getBlockPrefix():string
    {
        return '';
    }
}
