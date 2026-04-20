<?php

namespace App\Form;

use App\Entity\Seguidores;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SeguidoresType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status',ChoiceType::class,[
                'choices' => [
                    'APPROVED' => 'APPROVED',
                    'REJECTED' => 'REJECTED',
                ],
                'constraints' => [
                   new NotBlank(
                        message: 'El estado no puede estar vacío.',
                    )
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seguidores::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
    
}
