<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScenesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, [
                'label'    => 'Imagen',
                'required' => true,
                'mapped'   => false,       // No mapea a la entidad directamente
                'multiple' => false,       // Ahora acepta solo un fichero
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Agrega una imagen',
                    ]),
                    new Assert\File([
                        'maxSize'        => '25M',
                        'mimeTypes'      => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Por favor, carga una imagen en formato png, jpg o webp.',
                    ]),
                ],
            ])
        ;
    }

      public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
           /* 'data_class' => Usuarios::class,*/
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
    
}
