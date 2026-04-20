<?php

namespace App\Form;

use App\Entity\CategoriasTienda;
use App\Entity\Productos;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;



class CategoriasTiendaEditType extends AbstractType
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
          'data_class' => CategoriasTienda::class,
        ]);
    }


    public function getBlockPrefix():string
    {
        return '';
    }
}
