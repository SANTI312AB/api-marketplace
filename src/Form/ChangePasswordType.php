<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('old_password',PasswordType::class,[
            'label' => 'Contraseña actual',
            'required'=>true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor, introduce tu contraseña actual.',
                    ]),
                    new Length([
                        'min'=>4,
                        'max' =>30,
                        // max length allowed by Symfony for security reasons
                    ])
                    ]
        ])
        
        ->add('password',PasswordType::class, [
            'label' => 'Nueva contraseña',
            'required'=>true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Por favor, introduce tu nueva contraseña.',
                ]),
                new Length([
                    'min'=>4,
                    'max' =>30,
                    // max length allowed by Symfony for security reasons
                ]),

                new Regex([
                        
                    'pattern' => '/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).{6,}$/',
                    'message' =>"La contraseña debe tener caracteres especiales, números y letras"
                
                ])
                ],
        ]
    );


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }


    public function getBlockPrefix():string
    {
        return '';
    }
}
