<?php

namespace App\Form;

use App\Entity\CategoriasTienda;
use App\Entity\Productos;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class TSubcategoriasImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('imagen',FileType::class,[
            'required'=>false,
            'multiple' => false,

            'constraints' => [
                new File([
                    
                    'maxSize' => '500k',
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            //'data_class' => CategoriasTienda::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

}
