<?php

namespace App\Form;

use App\Entity\Ciudades;
use App\Entity\Estados;
use App\Entity\UsuariosDirecciones;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UsuariosDireccionesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('direccion_p',TextType::class,[
                'label' => 'Calle principal',
                'constraints' => [
                    new Length([
                        'min'=>2,
                        'max' =>60,
                        // max length allowed by Symfony for security reasons
                    ]),
                    new NotBlank([
                           'message' => "Agregue una dirección principal"
                    ]),
                ]
            ])
            ->add('direccion_s',TextType::class,[
                 "label"=>'Calle secundaria',
                'required'=>false,
                'constraints' => [
                    new Length([
                        'min'=>2,
                        'max' =>60,
                        // max length allowed by Symfony for security reasons
                    ]),
                    new NotBlank([
                        'message' => "Agregue una dirección secundaria"
                    ]),
                ],
            ])
            ->add('codigo_postal',NumberType::class,[
                'label'=>'Código postal',
                'required'=>false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[0-9,$]*$/',
                        'message' => "El campo solo debe tener números",
                    ]),
                    new NotBlank([
                        'message' => 'Agrega un código postal'
                    ]),
                ],
            ])
            ->add('etiqueta_direccion',TextType::class,[
                'label'=>'Ingresa una etiqueta',
                'required'=>false,
                'constraints' => [
                    new Length([
                        'min'=>2,
                        'max' =>60,
                        // max length allowed by Symfony for security reasons
                    ]),
                ],
            ])
            ->add('referencia_direccion',TextType::class,[
                'label'=>'Referencia',
                'required'=>false,
                'constraints' => [
                    new Length([
                        'min'=>2,
                        'max' =>100,
                        // max length allowed by Symfony for security reasons
                    ]),
                    new NotBlank([
                        'message' => 'Agrega un punto de referencia'
                    ]),
                ],
            ])

            ->add('default_direccion',TextType::class,[
                'required'=>false,
                'constraints' => [
                    new Length([
                        'min'=>2,
                        'max' =>60,
                        // max length allowed by Symfony for security reasons
                    ])
                ],
            ])
            ->add('n_casa',TextType::class,[
                'label'=>'Nro. de piso/apto/casa',
                'required'=>false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[0-9,$]*$/',
                        'message' => "El campo solo debe tener números",
                    ]),
                    new NotBlank([
                        'message' => 'Agrega un número de piso/apto/casa'
                    ]),
                ],
            ])
            ->add('ciudad',EntityType::class,[
                'class'=>Ciudades::class,
                'label'=>'Ciudad',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Seleccione una ciudad'
                    ]),
                ],
            ])
            ->add('latitud',null,[
                'label'=>'Latitud',
                 'constraints' => [
                    new NotBlank([
                        'message' => 'Agregue una latitud'
                    ]),
                ],
            ])
            ->add('longitud',null,[
                'label'=>'Longitud',
                 'constraints' => [
                    new NotBlank([
                        'message' => 'Agregue una longitud'
                    ]),
                ],
            ])

            ->add('observacion',TextType::class,[
                'label'=>'Observación',
                'constraints'=>[
                    new Length([
                        'min'=>2,
                        'max' =>300,
                    ])
                ]
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UsuariosDirecciones::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
