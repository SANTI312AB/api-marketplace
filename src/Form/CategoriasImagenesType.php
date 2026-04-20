<?php

namespace App\Form;

use App\Entity\Categorias;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoriasImagenesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('banner',FileType::class,[

                'constraints' => [
                    new File([
                        
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/webp',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Por favor, cargar una imágen con formato png,jpg,gift o webp ',
                    ])
                ],
            ])
            ->add('slider',FileType::class,[
                'constraints' => [
                    new File([
                        
                        'maxSize' => '512k',
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
            /*'data_class' => Categorias::class,*/
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
    
}
