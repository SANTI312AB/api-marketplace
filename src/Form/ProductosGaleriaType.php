<?php

namespace App\Form;

use App\Entity\ProductosGaleria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


class ProductosGaleriaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image',FileType::class,[
                'label' =>'Image',
                'required'=>true,
                'multiple' => true,
                "mapped"=>false,
                'constraints' => [
                    new Assert\All([
                        'constraints' => [
                            new Assert\NotBlank([
                                'message' => 'Agrega una imagen',
                            ]),

                            new Assert\File([
                                'maxSize' => '1200k',
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/jpg',
                                    'image/webp',
                                    'image/png',
                                    'image/gif'
                                ],
                                'mimeTypesMessage' => 'Por favor, cargar  imágenes con formato png, jpg, gif o webp. ',
                            ])
                         
                        ],
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    { 

        $resolver->setDefaults([
            /*'data_class' => ProductosGaleria::class,*/
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
}
