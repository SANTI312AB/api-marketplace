<?php

namespace App\Form;

use App\Entity\Factura;
use App\Validator\Constraints\CedulaEcuatoriana;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class FacturaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre',TextType::class,[

                'constraints' => [
                    new Length([
                        'min'=>2,
                        'max' =>30,
                        // max length allowed by Symfony for security reasons
                    ]),
                    new NotBlank([
                        'message'=>'Agrega un nombre.'
                    ])
                ],
            ])
            ->add('apellido',TextType::class,[

                'constraints' => [
                    new Length([
                        'min'=>2,
                        'max' =>30,
                        // max length allowed by Symfony for security reasons
                    ]),

                    new NotBlank([
                        'message'=>'Agrega un apellido.'
                    ])
                ],
            ])
            ->add('telefono',TextType::class,[
                'constraints' => [
                    new NotBlank([
                        'message'=>'Agrega un teléfono.'
                    ]),
            
                    new Length([
                        'min'=>9,
                        'max' =>10,
                        // max length allowed by Symfony for security reasons
                    ]),

                    new Regex([
                        'pattern' => '/^[0-9,$]*$/',
                        'message' => "El campo solo debe tener números",
                    ]),


                   
                ],
            ])
            ->add('email',EmailType::class,[

                'constraints' => [
                    new Length([
                        'min'=>4,
                        'max' =>30,
                        // max length allowed by Symfony for security reasons
                    ]),
                    new NotBlank([
                        'message'=>'Agrega un email.'
                    ])
                ]
            ])
            ->add('dni',    TextType::class,[
                'constraints' => [
                    new NotBlank([
                        'message'=>'Agrega un dni.'
                    ]),
                    new Length([
                        'min'=>10,
                        'max' =>10,
                        // max length allowed by Symfony for security reasons
                    ]),
                    NEW Regex([
                        'pattern' => '/^[0-9]+$/',
                           'message' => 'EL DNI debe tener solo números.',
                        ]),    
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Factura::class,
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
    
}
