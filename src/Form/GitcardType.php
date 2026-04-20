<?php

namespace App\Form;

use App\Entity\Productos;



use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Range;
class GitcardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('email', EmailType::class,[
            'label'=>'Email',
            'required'=>false,
            'constraints' => [
         
                new Length([
                    'min'=>4,
                    'max' =>30,
                ]),

                new NotBlank([
                    'message' => 'Agrega una correo para la Gift Card',
                ]),
            ],
        ])
        ->add('nombre',TextType::class,[
            'label'=>'Nombre',
            'required'=>false,
            'constraints' => [
        
                new Length([
                    'min'=>4,
                    'max' =>100,
                ]),

                new NotBlank([
                    'message' => 'Agrega un nombre para la Gift Card',
                ]),
            ],
        ])
        ->add('precio',NumberType::class,[
            'label'=>'Precio Normal del Producto',
            'required'=>true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Agrega un precio a la GitCard',
                ]),
                new Range(['min' =>5 , 'max' =>50]),
            ]
        ])

        ->add('tipo',ChoiceType::class,[
             'label'=>'Tipo de Cupon',
             'required'=>false,
             'choices'=>[
                'GIFTCARD'=>'GIFTCARD',
                'RECARGA'=>'RECARGA'               
             ],
             'constraints' => [
                new NotBlank([
                    'message' => 'Agrega un precio a la GitCard',
                ]),
            ],
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
           // 'data_class' => Productos::class,
        ]);
    }


    public function getBlockPrefix():string
    {
        return '';
    }
}

