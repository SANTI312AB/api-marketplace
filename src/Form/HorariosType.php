<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HorariosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dia',ChoiceType::class,[
                'choices' => [
                    'Lunes' => 'Lunes',
                    'Martes' => 'Martes',
                    'Miércoles' => 'Miércoles',
                    'Jueves' => 'Jueves',
                    'Viernes' => 'Viernes',
                    'Sábado' => 'Sábado',
                    'Domingo' => 'Domingo'
                ],
                'multiple' => true
            ])
            ->add('horas',ChoiceType::class,[
                'choices' => [
                    '0' => '0',
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12',
                    '13' => '13',
                    '14' => '14',
                    '15' => '15',
                    '16' => '16',
                    '17' => '17',
                    '18' => '18',
                    '19' => '19',
                    '20' => '20',
                    '21' => '21',
                    '22' => '22',
                    '23' => '23'           
                ],
               'multiple' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
