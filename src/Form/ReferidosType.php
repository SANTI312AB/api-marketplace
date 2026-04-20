<?php

namespace App\Form;

use App\Entity\Cupon;
use App\Entity\Login;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;

class ReferidosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipo_descuento', ChoiceType::class, [
            'choices' => [
                'PORCENTAJE' => 'PORCENTAJE'
            ],
            'constraints' => [
                new NotBlank([
                'message' => 'Elija un tipo de descuento.',
                ])
            ]
            ])

            ->add('limite_uso', NumberType::class, [
            'constraints' => [
                new Assert\LessThanOrEqual([
                    'value' => 10,
                    'message' => 'El número no puede ser mayor a 10.',
                ]),
                new Assert\GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'El número no puede ser menor a 1.',
                ]),
            ]
            ])
            ->add('login', EntityType::class, [
                'label' => 'Login',
                'class' => Login::class,
                'multiple' => true,
                'required' => false,
                'choice_value' => 'email', // Cambiado de 'id' a 'email'
            ])

            ->add('descripcion', TextType::class, [
            'constraints' => [
                new Assert\Length([
                'max' => 300,
                'maxMessage' => 'El texto no debe ser mayor a 300 caracteres.',
                ])
            ]
            ])
        ;



    }

    

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cupon::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
    
}