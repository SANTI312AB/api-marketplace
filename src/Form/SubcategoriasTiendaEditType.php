<?php

namespace App\Form;

use App\Entity\CategoriasTienda;
use App\Entity\Productos;
use App\Entity\SubcategoriasTiendas;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubcategoriasTiendaEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubcategoriasTiendas::class,
        ]);
    }


    public function getBlockPrefix(): string
    {
        return '';
    }
    
}
