<?php

namespace App\Form;

use App\Entity\CategoriasTienda;
use App\Entity\Productos;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TProductosCategoriasType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('productos', EntityType::class, [
                'class' => Productos::class,
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
          // 'data_class' => CategoriasTienda::class,
        ]);
    }

    
    public function getBlockPrefix(): string
    {
        return '';
    }
}
