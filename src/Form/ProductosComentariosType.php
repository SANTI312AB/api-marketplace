<?php

namespace App\Form;

use App\Entity\ProductosComentarios;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ProductosComentariosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comentario',TextareaType::class,[   
                'constraints' => [
                    new NotBlank([
                        'message' => 'Agrega un comentario al producto',
                    ]),
                    new Length([
                        'min'=>5,
                        'max' =>300,
                        // max length allowed by Symfony for security reasons
                    ]),
                ],

            ])
            ->add('calificacion',NumberType::class,[
                
                'constraints' =>[
                    new NotBlank([
                        'message' => 'Agrega una calificacion al producto',
                    ]),
                    new Range(['min' =>1,'max'=>5]),
                ] 
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductosComentarios::class,
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
}
