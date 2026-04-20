<?php

namespace App\Form;

use App\Entity\Hotspot;
use App\Entity\Productos;
use App\Entity\Scenes;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HotspotsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('yaw',NumberType::class)
            ->add('pitch',NumberType::class)
            ->add('type',ChoiceType::class,[
                'choices' => [
                    'link' => 'link',
                    'info' => 'info'
                ],
            ])
            ->add('text')
            ->add('url')
            ->add('slug_producto',TextType::class,[
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Hotspot::class,
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }

}
