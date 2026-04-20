<?php

namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class SubcategoriasTiendaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre')
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
          /*  'data_class' => SubcategoriasTiendas::class,*/
        ]);
    }


    public function getBlockPrefix(): string
    {
        return '';
    }
}
