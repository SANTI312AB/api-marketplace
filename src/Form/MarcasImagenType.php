<?php

namespace App\Form;

use App\Entity\Categorias;
use App\Entity\ProductosMarcas;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class MarcasImagenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('logo',FileType::class,[

                'constraints' => [
                    new File([
                        
                        'maxSize' => '512k',
                        'mimeTypes' => [
                            'image/png',
                            'image/svg+xml'
                        ],
                        'mimeTypesMessage' => 'Por favor, cargar una imagen con formato png o svg',
                    ])
                ],

            ])
    
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
             /*'data_class' => ProductosMarcas::class,*/
        ]);
    }

    
    public function getBlockPrefix():string
    {
        return '';
    }
}
