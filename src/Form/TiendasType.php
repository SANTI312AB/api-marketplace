<?php

namespace App\Form;

use App\Entity\Tiendas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Doctrine\ORM\EntityManagerInterface;

class TiendasType extends AbstractType
{
    private $entityManager;


    // Inyecta el EntityManager en el FormType
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre_tienda')
            ->add('descripcion')
            ->add('celular',TextType::class,[
                'constraints' => [
                    new Regex([
                    'pattern' => '/^[0-9,$]*$/',
                    'message' => "El campo solo debe tener números",
                ]),
                new Length([
                    'min'=>10,
                    'max' =>10,
                ]),
                ]   

            ])
            ->add('email',EmailType::class)
            ->add('ruc_tienda',TextType::class,[
                'constraints' => [
                    new Regex([
                    'pattern' => '/^[0-9,$]*$/',
                    'message' => "El campo solo debe tener números",
                    ]),

                    new Length([
                        'min'=>13,
                        'max' =>13,
                    ]),
                ] 
            ])
            ->add('nombre_contacto') 
        ;
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
           'data_class' => Tiendas::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
    
}
