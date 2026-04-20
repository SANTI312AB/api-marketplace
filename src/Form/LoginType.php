<?php

namespace App\Form;
use App\Entity\Login;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email',EmailType::class,[
                'label' => 'email', 
                'constraints' => [
                    new NotBlank([
                        'message' => 'Agrega un correo',
                    ]),
                    new Length([
                        'min'=>4,
                        'max' =>50,
                        // max length allowed by Symfony for security reasons
                    ]),
                ],
            ])
            ->add('username',TextType::class,[
                'label' => 'username', 
                'constraints' => [
                    new NotBlank([
                        'message' => 'Agrega un nombre de usuario.',
                    ]),
                    new Length([
                        'min'=>4,
                        'max' =>50,
                        // max length allowed by Symfony for security reasons
                    ]),

                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9]+$/',
                        'message' => 'El nombre de usuario sólo debe tener letras y números.',
                    ])

                ],
            ])
            ->add('nombre',TextType::class,[
                'label' => 'nombre', 
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Este campo debe tener datos',
                    ]),
                    new Length([
                        'min'=>1,
                        'max' =>30,
                        // max length allowed by Symfony for security reasons
                    ]),
                ],
            ])
            ->add('apellido',TextType::class,[
                'label' => 'nombre', 
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Este campo debe tener datos',
                    ]),
                    new Length([
                        'min'=>1,
                        'max' =>30,
                        // max length allowed by Symfony for security reasons
                    ]),
                ],
            ])

            ->add('password',PasswordType::class,[
                'label' => 'password', 
                'constraints' => [
                    new NotBlank([
                        'message' => 'Agrega una contraseña',
                    ]),
                    new Length([
                        'min'=>4,
                        'max' =>200,
                        // max length allowed by Symfony for security reasons
                    ]),

                    new Regex([
                            
                        'pattern' => '/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).{6,}$/',
                        'message' =>"La contraseña debe tener caracteres especiales, números y letras"
                    
                    ])
                ],
            ])

            ->getForm();

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Login::class,
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
}
