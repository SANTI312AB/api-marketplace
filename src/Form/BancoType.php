<?php

namespace App\Form;

use App\Entity\Banco;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class BancoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre_cuenta',TextType::class,[
                'constraints' => [
                    new NotBlank([
                        'message' => 'Añade un nombre de cuenta.',
                    ]), 
                ],
            ])
            ->add('numero_cuenta',NumberType::class,[
                'constraints' => [
                    new NotBlank([
                        'message' => 'Añade un numero de cuenta.',
                    ]), 
                    new Length([
                       'min'=>4,
                       'max' =>20,
                    ])
                ],
            ])
            ->add('tipo_cuenta',ChoiceType::class, [ 
                'constraints' => [
                    new NotBlank([
                        'message' => 'Elije una opcion.',
                    ]), 
                ],
                'choices'  => [
                    'Cuenta Corriente' => 'Cuenta Corriente',
                    'Cuenta Ahorros' => 'Cuenta Ahorros',
                ],
            ])
            ->add('banco',TextType::class,[
                'constraints' => [
                    new NotBlank([
                        'message' => 'Añade un nombre de un Banco.',
                    ]), 
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Banco::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
    
}
