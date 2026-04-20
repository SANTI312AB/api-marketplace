<?php

namespace App\Form;

use App\Entity\Tiendas;
use App\Entity\VirtualTour;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class VirtualTourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre',TextType::class,[
                'label' => 'Nombre del tour virtual',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Agrega un nombre al tour virtual',
                    ]),
                    new Length([
                        'min' => 4,
                        'max' => 100,
                    ]),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VirtualTour::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
