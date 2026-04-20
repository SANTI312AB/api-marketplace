<?php

namespace App\Form;

use App\Entity\Terminos;
use App\Entity\Variaciones;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class VariacionesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
    
            ->add('descripcion',TextType::class,[
                'label'=>'Descripcion',
                'constraints' => [          
                    new Length([
                        'min'=>4,
                        'max' =>500,
                    ]),
                ],
            ])
            ->add('precio', NumberType::class, [
                'label' => 'Precio',
                'required' => true,
                'scale' => 2, // asegura dos decimales en el formulario
            ])
            ->add('descuento',NumberType::class,[
                'label'=>'Descuento',
                'required'=>false,
                'mapped'=>false,
                'constraints' =>[
                      new Range(['min' =>0,'max'=>100]),
                      new Regex([
                        'pattern' => '/^[0-9,$]*$/',
                        'message' => "El campo solo debe tener números",
                    ]),
                ] 
             ])
            ->add('cantidad',NumberType::class,[
                'label'=>'Cantidad',
                'constraints' => [

                    new Regex([
                        'pattern' => '/^[0-9,$]*$/',
                        'message' => "El campo solo debe tener números",
                    ]),
                ],
            ])
            ->add('sku',TextType::class,[
                'label'=>'SKU',
                'required'=>true,
                'constraints' => [
                    new Length([
                        'min'=>4,
                        'max' =>40,
                    ])
                ],
            ])
            ->add('terminos',EntityType::class,[
                'label'=>'Variaciones',  // Cambia el label
                'class'=>Terminos::class,
                'multiple' => true,
                'required' => false, // Cambia required a false
                'constraints' => [
                    new Assert\All([
                        'constraints' => [
                            new Assert\NotBlank([
                                'message' => 'Agregar variaciones',
                            ]),
                        ],
                    ]),
                ],
                
            ])

            ->add('codigo_variante', TextType::class, [  
                'label' => 'Código de Variante',
                'constraints' => [
                    new NotBlank(['message' => 'Este campo no puede estar vacío']),
                    new Length(['min' => 4, 'max' => 20])
                ]
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Variaciones::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
    
}
