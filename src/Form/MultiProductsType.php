<?php

namespace App\Form;

use App\Entity\Categorias;
use App\Entity\CategoriasTienda;
use App\Entity\EntregasTipo;
use App\Entity\Estados;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Productos;
use App\Entity\ProductosMarcas;
use App\Entity\ProductosVentas;
use App\Entity\Subcategorias;
use App\Entity\SubcategoriasTiendas;
use App\Entity\Tiendas;
use App\Entity\UsuariosDirecciones;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\Callback;

class MultiProductsType extends AbstractType
{

    private $entityManager;
    private $security;

    // Inyecta el EntityManager en el FormType
    public function __construct(EntityManagerInterface $entityManager,Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('productos', EntityType::class, [
            'class' => Productos::class,
            'label' =>'Productos',
            'multiple' => true,
            'constraints' => [
                new Assert\All([
                    'constraints' => [
                        new Assert\NotBlank([
                            'message' => 'Seleccione productos',
                        ])
                    ],
                ]),
            ],
        ])

        ->add("marcas",EntityType::class,[
            'label'=>'Marcas',
            'required'=>false,
            'class'=>ProductosMarcas::class,
            'choice_label'=>'nombre_m'
        ])

        ->add('estado',EntityType::class,[
            'label'=>'Estado',
            'required'=>true,
            'class'=>Estados::class,
            'choice_label'=>'nobre_estado'

        ])

        ->add('productos_ventas',EntityType::class,[
            'label'=>'Tipo de venta',
            'required'=>true,
            'class'=> ProductosVentas::class,
            'choice_label'=>'tipo_venta'
        ])


        ->add('entrgas_tipo',EntityType::class,[
            'label'=>'Tipo de Entrega',  // Nombre del label en el formulario
            'required'=>true,
            'class'=>EntregasTipo::class,
            'choice_label'=>'tipo'
        ])

        ->add('categorias', EntityType::class, [
            'label'=>'Categorias',  // Nombre del label en el formulario
            'class' => Categorias::class,
            'choice_label' => 'nombre',
            'multiple' => true,
            'required' => false, // Cambia required a false
            
        ])

        ->add('subcategorias', EntityType::class, [
            'label'=>'Subcategorias',  // Nombre del label en el formulario
            'class' => Subcategorias::class,
            'choice_label' => 'nombre',
            'multiple' => true,
            'required' => false, // Cambia required a false

        ])

        

        ->add('categoriasTiendas',EntityType::class,[
            'label'=>'Categorias de la Tienda',
            'class' => CategoriasTienda::class,
            'multiple' => true,
        ])

        ->add('subcategoriasTiendas', EntityType::class, [
            'label'=>'Subcategorias de Tiendas',  // Nombre del label en el formulario
            'class' => SubcategoriasTiendas::class,
            'choice_label' => 'nombre',
            'multiple' => true,
            'required' => false, // Cambia required a false

        ])

        ->add('direcciones',EntityType::class,[
            'label'=>'Dirección',
            'required'=>false,
            'class'=> UsuariosDirecciones::class,
            'constraints' => [
                      
                        new Callback([$this,'validateDireccion']),
                    ] 
         ])

         ->add('disponibilidad_producto', CheckboxType::class, [
            'label' => 'Disponibilidad del producto',
            'required' => false, // Permite enviar el formulario sin marcar esta casilla
        ])
        ->add('borrador', CheckboxType::class, [
            'label' => 'Es borrador',
            'required' => false,
        ])
        ;
    }



    public function validateDireccion($value, ExecutionContextInterface $context)
    {
        $user = $this->security->getUser();
        $user_id = $user->getUsuarios()->getId();
        if ($value) {
            // Aquí se verifica si el ID es correcto
           
            // dump($id); // Descomenta esto para ver el ID

            $entity = $this->entityManager->getRepository(UsuariosDirecciones::class)->findBy(['id' => $value,'usuario'=>$user_id]);

            if (!$entity) {
                $context->buildViolation('La dirección seleccionada no te pertenece.')
                    ->atPath('direcciones')
                    ->addViolation();

                 
            } 
        }
    }


     


   
    

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
}
