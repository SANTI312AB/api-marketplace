<?php
namespace App\Form;

use App\Entity\Productos;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteMultipleProductosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('productos', EntityType::class, [
                'class' => Productos::class,
                'label' => 'Productos a eliminar',
                'multiple' => true,
                'constraints' => [
                    new Assert\All([
                        'constraints' => [
                            new Assert\NotBlank([
                                'message' => 'Seleccione productos',
                            ]),
                        ],
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            //'csrf_protection' => false, // Deshabilitado para una API
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
    
}
