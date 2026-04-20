<?php

namespace App\Form;

use App\Entity\Banco;
use App\Entity\Retiros;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RetiroType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
           
            ->add('banco',EntityType::class,[
                'class'=>Banco::class,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Seleccione una cuenta de banco para transferir sus ganancias.',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Retiros::class,
        ]);
    }


    public function getBlockPrefix(): string
    {
        return '';
    }
    
}
