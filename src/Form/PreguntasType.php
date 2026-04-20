<?php

namespace App\Form;

use App\Entity\Login;
use App\Entity\Preguntas;
use App\Entity\Productos;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PreguntasType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pregunta',TextType::class,[
                'constraints' => [
                    new Length([
                        'min'=>5,
                        'max' =>300
                    ]),
                    new NotBlank([
                        'message' => 'Agrega una pregunta',
                    ]),

                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Preguntas::class,
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
}
