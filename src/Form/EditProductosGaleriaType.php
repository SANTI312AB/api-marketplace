<?php

namespace App\Form;

use App\Entity\ProductosGaleria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


class EditProductosGaleriaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image',FileType::class,[
                'label' => 'Imagen',
                'multiple' => false,
                "mapped"=>false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    { 

        $resolver->setDefaults([
            /*'data_class' => ProductosGaleria::class,*/
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
}
