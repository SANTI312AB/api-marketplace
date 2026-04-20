<?php

namespace App\Form;

use App\Entity\Regateos;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegateoPType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('estado',ChoiceType::class,[
                 'constraints' => [
                    new NotBlank(['message' => 'Elija una opción.']),
                ],
                'choices'=>[
                    'APPROVED'=>'APPROVED',
                    'REJECTED'=>'REJECTED'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Regateos::class,
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
}
