<?php

namespace App\Form;

use App\Entity\Productos;
use App\Entity\Subastas;
use App\Entity\Variaciones;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SubastasType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fin_subasta', DateTimeType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La fecha de fin de subasta no puede estar vacía.',
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La fecha de finalización de la subasta debe ser superior a la fecha actual.',
                    ]),
                ]
            ])
            ->add('valor_minimo', NumberType::class, [
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Agrega una entrega',
                    ]),
                    new Assert\Range([
                        'min' => 1
                    ]),
                ]
            ])
            ->add('IdVariacion', EntityType::class, [
                'class' => Variaciones::class
            ])
            ->add('activo')
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subastas::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

}