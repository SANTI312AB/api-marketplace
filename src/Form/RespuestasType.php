<?php

namespace App\Form;

use App\Entity\login;
use App\Entity\Preguntas;
use App\Entity\Respuestas;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class RespuestasType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('respuesta',TextType::class,[
                'constraints' => [
                    new Length([
                        'min'=>5,
                        'max' =>300
                    ]),
                ],
            ])
  
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Respuestas::class,
        ]);
    }


    public function getBlockPrefix(): string
    {
        return '';
    }

}
