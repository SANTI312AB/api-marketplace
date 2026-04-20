<?php

namespace App\Form;

use App\Entity\LogsFront;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Factory\Cache\ChoiceAttr;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class LogsFrontType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('message',TextType::class,[
                 'constraints' => [
                      new NotBlank(['message' => 'Agrega datos.']),
                      new Length([
                        'max' =>500,
                      ])
                 ]
            ])
            ->add('contex',TextType::class,[
                'constraints' => [
                      new NotBlank(['message' => 'Agrega datos.']),
                      new Length([
                        'max' =>500,
                      ])
                 ]
            ])
            ->add('level',ChoiceType::class,[
                 'constraints' => [
                      new NotBlank(['message' => 'Agrega datos.']),
                 ],
                 'choices' => [
                    'error'    => 'error',
                    'info'       => 'info',
                    'warning' => 'warning',
                ],
            ])
            ->add('meta',TextType::class,[
                  'required'=>false,
                  'constraints' => [
                      new Length([
                        'max' =>500,
                      ])
                 ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LogsFront::class,
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
}
