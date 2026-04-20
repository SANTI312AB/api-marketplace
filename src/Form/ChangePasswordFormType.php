<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('token',TextType::class,[
            'constraints' => [
                new NotBlank([
                    'message' => 'Agrega un token para actualizar la contraseña.',
                ])
                ]

        ])
        ->add('password',PasswordType::class, [
            'constraints' => [
                new NotBlank([
                    'message' => 'Agrega una contraseña',
                ]),
                new Length([
                    'min'=>4,
                    'max' =>30,
                ]),

                new Regex([
                        
                    'pattern' => '/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).{6,}$/',
                    'message' =>"La contraseña debe tener caracteres especiales, números y letras"
                
                ])
                ],
        ]
    )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }


    public function getBlockPrefix():string
    {
        return '';
    }
}
