<?php

namespace App\Form;

use App\Entity\Categorias;
use App\Entity\CategoriasTienda;
use App\Entity\EntregasTipo;
use App\Entity\Estados;
use App\Entity\Login;
use App\Entity\Productos;
use App\Entity\ProductosMarcas;
use App\Entity\ProductosTipo;
use App\Entity\ProductosVentas;
use App\Entity\Subcategorias;
use App\Entity\SubcategoriasTiendas;
use App\Entity\UsuariosDirecciones;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Range;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Bundle\SecurityBundle\Security;


class ProductosType extends AbstractType
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
            ->add('nombre_producto',TextType::class,[
                'label'=>'Nombre del producto',
                'required'=> true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Agrega un nombre al producto',
                    ]),
                    new Length([
                        'min'=>2,
                        'max' =>250,
                    ]),

                    new Callback([$this,'n_producto']),
                ],
            ])
            ->add('descripcion_corta_producto',TextType::class,[
                'label'=>'Descripcion corta del producto',
                'required'=>false,
                'constraints' => [
             
                    new Length([
                        'min'=>4,
                        'max' =>300,
                    ]),
                ],
            ])
            ->add('descripcion_larga_producto',TextareaType::class,[
                'label'=>'Descripcion larga del producto',
                'required'=>false,
                'constraints' => [
            
                    new Length([
                        'min'=>4,
                        'max' =>7000,
                    ]),

                    new NotBlank([
                        'message' => 'Agrega una descripción detallada del producto',
                    ]),
                ],
            ])

            ->add('ficha_tecnica',TextareaType::class,[
                'label'=>'Ficha Técnica',
                'required'=>false,
                'constraints' => [
                    new Length([
                        'min'=>4,
                        'max' =>7000,
                    ]),
                ],
            ])
           
            ->add('precio_normal_producto',NumberType::class,[
                'label'=>'Precio Normal del Producto',
                'required'=>true,
                'scale' => 2, // asegura dos decimales en el formulario
                'constraints' => [
                    new NotBlank([
                        'message' => 'Agrega un precio al producto',
                    ]),
                ]
            ])
        
            
            ->add('cantidad_producto',NumberType::class,[
                'label'=>'Cantidad del Producto',
                'required'=>false,
                'constraints' => [
                    /*new NotBlank([
                        'message' => 'Agrege una cantidad',
                    ]),*/

                    new Regex([
                        'pattern' => '/^[0-9,$]*$/',
                        'message' => "El campo solo debe tener números",
                    ]),
                ],
            ])

            ->add('video_producto',TextType::class,[
                'label'=>'Video del Producto',
                'required'=>false
            ])

            ->add("marcas",EntityType::class,[
                'label'=>'Marcas',
                'required'=>false,
                'class'=>ProductosMarcas::class,
                'choice_label'=>'nombre_m',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Seleccione una marca',
                    ]),
                ],
            ])

            ->add('estado',EntityType::class,[
                'label'=>'Estado',
                'required'=>true,
                'class'=>Estados::class,
                'choice_label'=>'nobre_estado',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Agrega un estado',
                    ]),
                ],

            ])

            ->add('productos_ventas',EntityType::class,[
                'label'=>'Tipo de venta',
                'required'=>true,
                'class'=> ProductosVentas::class,
                'choice_label'=>'tipo_venta',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Agrega un tipo de venta',
                    ]),
                ],
            ])


            ->add('entrgas_tipo',EntityType::class,[
                'label'=>'Tipo de Entrega',  // Nombre del label en el formulario
                'required'=>true,
                'class'=>EntregasTipo::class,
                'choice_label'=>'tipo',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Selecciona un método de entrega',
                    ]),
                ],
            ])


            ->add('categorias', EntityType::class, [
                'label'=>'Categorias',  // Nombre del label en el formulario
                'class' => Categorias::class,
                'choice_label' => 'nombre',
                'multiple' => true,
                'required' => false, // Cambia required a false
                'constraints' => [
                    new Assert\All([
                        'constraints' => [
                            new Assert\NotBlank([
                                'message' => 'Selecciona una categoria',
                            ]),
                        ],
                    ]),
                ],
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

            
            ->add('sku_producto',TextType::class,[
                'label'=>'SKU del Producto',
                'required'=>false,
                'constraints' => [
                    new Length([
                        'min'=>4,
                        'max' =>20,
                    ])
                ],
            ])  
            ->add('ean_producto',TextType::class,[
                'label'=>'EAN del Producto',
                'required'=>false,
                'constraints' => [
                    new Length([
                        'min'=>10,
                        'max' =>30,
                    ]),

                    new Regex([
                        'pattern' => '/^[0-9,$]*$/',
                        'message' => "El ean del prooducto solo debe tener números",
                    ]),
                   /*
                    new NotBlank([
                        'message' => 'Agrega un codigo de barra (EAN)',
                    ]),*/

                ] 

                ])

                ->add('regateo_producto',null,[
                    'label'=>'Regateo del Producto'
                ])

                ->add('variable',null,[
                    'label'=>'Variable del Producto'
                ])
              
                ->add('garantia_producto',TextType::class,[
                     'label'=>'Garantia del Producto',
                    'required'=>false,
                    'constraints' => [
                        new Length([
                            'min'=>4,
                            'max' =>300,
                        ]),
                    ],
                ])

                ->add('etiquetas_producto',TextType::class,[
                    'required'=>false,
                    'label'=>'Etiquetas Productos'

                ])

                ->add('largo',NumberType::class,[
                    'label'=>"Largo"
                ])
                
                ->add('ancho',NumberType::class,[
                    'label'=>"Ancho"
                ])
                ->add('alto',NumberType::class,[
                    'label'=>"Alto"
                ])
                ->add('peso',NumberType::class,[
                    'label'=>"Peso",
                    'constraints' => [
                         
                        new Range([
                            'max' =>15,
                            'maxMessage' => 'El peso del producto debe ser menor a 15 kg',
                        ]),
                        
                    ],
                ])


                 ->add('descuento',NumberType::class,[
                    'label'=>'Descuento',
                    'mapped'=>false,
                    'required'=>false,
                    'constraints' => new Range(['min' =>0,'max'=>100]),
                 ])

                 ->add('direcciones',EntityType::class,[
                    'label'=>'Dirección',
                    'required'=>false,
                    'class'=> UsuariosDirecciones::class,
                    'constraints' => [
                     
                        new NotBlank([
                            'message' => 'Por favor, selecciona una dirección.',
                        ]), 
                        new Callback([$this,'validateDireccion']),
                    ] 
                 ])
                 ->add('tiene_iva')
                 ->add('impuestos_incluidos')

                 ->add('tiempo_entrega',NumberType::class)
                 ->add('productos_tipo',EntityType::class,[
                    'label'=>'Tipo de Producto',
                    'required'=>true,
                    'class'=> ProductosTipo::class,
                    'choice_label'=>'id'
                 ])

                 ->add('codigo_producto',TextType::class,[
                    'label'=>'Código del Producto',
                    'required'=>false,
                    'constraints' => [
                        new Length([
                            'min'=>4,
                            'max' =>20,
                        ])
                    ],
                 ])

                 
            /*     
               
            ->add('meta_producto')

           
            */
        ;
    }
    
    public function validateDireccion($value, ExecutionContextInterface $context)
    {
        $user = $this->security->getUser();
        
        if ($user instanceof Login){

            $user_id = $user->getUsuarios()->getId();
         }
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


    public function n_producto($value, ExecutionContextInterface $context)
    {
        $user = $this->security->getUser();
        if ($user instanceof Login) {
            $tienda = $user->getTiendas()->getId();
        }

        $productoEnEdicion = $context->getObject()->getParent()->getData();

        // Verificar si es una edición (no nuevo registro)
        if ($productoEnEdicion->getId() !== null) {
            $unitOfWork = $this->entityManager->getUnitOfWork();
            $originalData = $unitOfWork->getOriginalEntityData($productoEnEdicion);
            $originalNombre = $originalData['nombre_producto'] ?? null;

            // Si el nombre no ha cambiado, omitir validación
            if ($value === $originalNombre) {
                return;
            }
        }

        if ($value) {
            $existingProduct = $this->entityManager->getRepository(Productos::class)
                ->findOneBy([
                    'nombre_producto' => $value,
                    'tienda' => $tienda,
                    'productos_tipo' => [1, 2]
                ]);

            if ($existingProduct && $existingProduct->getId() !== $productoEnEdicion->getId()) {
                $context->buildViolation('Este producto ya existe en la tienda.')
                    ->atPath('nombre_producto')
                    ->addViolation();
            }
        }
    }




    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Productos::class,
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
    
}
